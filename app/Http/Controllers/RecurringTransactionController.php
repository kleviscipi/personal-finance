<?php

namespace App\Http\Controllers;

use App\Models\RecurringTransaction;
use App\Services\CurrencyService;
use App\Services\RecurringTransactionService;
use App\Support\ActiveAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RecurringTransactionController extends Controller
{
    public function __construct(
        private RecurringTransactionService $recurringTransactionService,
        private CurrencyService $currencyService
    ) {}

    public function index(Request $request)
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $recurringTransactions = $account->recurringTransactions()
            ->with(['category', 'subcategory', 'creator', 'user', 'tags'])
            ->orderBy('next_run_date')
            ->orderBy('id')
            ->get();

        $dueCount = $account->recurringTransactions()
            ->where('is_active', true)
            ->whereDate('next_run_date', '<=', now()->toDateString())
            ->count();

        return Inertia::render('RecurringTransactions/Index', [
            'currentAccount' => $account,
            'recurringTransactions' => $recurringTransactions,
            'dueCount' => $dueCount,
        ]);
    }

    public function create(Request $request)
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $this->authorizeManagement($account, $request);

        return Inertia::render('RecurringTransactions/Create', [
            'currentAccount' => $account,
            'categories' => $this->categoriesForAccount($account),
            'accountUsers' => $this->accountUsersForAccount($account),
            'currentUserId' => $request->user()->id,
            'tags' => $account->tags()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $this->authorizeManagement($account, $request);

        $validated = $this->validatePayload($request, $account->id);
        $validated['user_id'] = !empty($validated['user_id']) ? (int) $validated['user_id'] : $request->user()->id;
        $this->authorizeTransactionUserScope($account, $request, $validated['user_id']);
        $validated['tag_ids'] = $validated['tag_ids'] ?? [];
        $validated['tag_names'] = $this->parseTagNames($request->input('tag_names'));

        $this->recurringTransactionService->createRecurringTransaction($account, $request->user(), $validated);

        return redirect()
            ->route('recurring-transactions.index')
            ->with('message', 'Recurring transaction created successfully.');
    }

    public function edit(Request $request, RecurringTransaction $recurringTransaction)
    {
        $this->authorize('update', $recurringTransaction);

        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        return Inertia::render('RecurringTransactions/Edit', [
            'currentAccount' => $account,
            'recurringTransaction' => $recurringTransaction->load(['category', 'subcategory', 'tags', 'user']),
            'categories' => $this->categoriesForAccount($account),
            'accountUsers' => $this->accountUsersForAccount($account),
            'currentUserId' => $request->user()->id,
            'tags' => $account->tags()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, RecurringTransaction $recurringTransaction)
    {
        $this->authorize('update', $recurringTransaction);

        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $validated = $this->validatePayload($request, $account->id);
        $validated['user_id'] = !empty($validated['user_id']) ? (int) $validated['user_id'] : $request->user()->id;
        $this->authorizeTransactionUserScope($account, $request, $validated['user_id']);
        $validated['tag_ids'] = $validated['tag_ids'] ?? [];
        $validated['tag_names'] = $this->parseTagNames($request->input('tag_names'));

        $this->recurringTransactionService->updateRecurringTransaction($recurringTransaction, $validated);

        return redirect()
            ->route('recurring-transactions.index')
            ->with('message', 'Recurring transaction updated successfully.');
    }

    public function destroy(RecurringTransaction $recurringTransaction)
    {
        $this->authorize('delete', $recurringTransaction);

        $this->recurringTransactionService->deleteRecurringTransaction($recurringTransaction);

        return redirect()
            ->route('recurring-transactions.index')
            ->with('message', 'Recurring transaction deleted successfully.');
    }

    public function runDue(Request $request)
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $this->authorizeManagement($account, $request);

        $result = $this->recurringTransactionService->runDueTransactions(now(), $account, $request->user());

        return redirect()
            ->route('recurring-transactions.index')
            ->with(
                'message',
                "Processed {$result['templates_processed']} recurring templates and created {$result['transactions_created']} transactions."
            );
    }

    private function validatePayload(Request $request, int $accountId): array
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['expense', 'income', 'transfer'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(array_keys($this->currencyService->getSupportedCurrencies()))],
            'next_run_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:next_run_date'],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'interval' => ['required', 'integer', 'min:1', 'max:365'],
            'user_id' => [
                'nullable',
                Rule::exists('account_user', 'user_id')->where(fn ($query) => $query->where('account_id', $accountId)),
            ],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('account_id', $accountId)),
            ],
            'subcategory_id' => ['nullable', Rule::exists('subcategories', 'id')],
            'description' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'tag_ids' => ['array'],
            'tag_ids.*' => [
                Rule::exists('tags', 'id')->where(fn ($query) => $query->where('account_id', $accountId)),
            ],
            'tag_names' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->recurringTransactionService->validateSubcategory($validated, $accountId);
    }

    private function authorizeManagement($account, Request $request): void
    {
        $pivot = $account->users()
            ->where('users.id', $request->user()->id)
            ->first()
            ?->pivot;

        abort_unless($pivot && $pivot->is_active && in_array($pivot->role, ['owner', 'admin', 'member'], true), 403);
    }

    private function categoriesForAccount($account)
    {
        return $account->categories()
            ->with(['subcategories' => function ($query) {
                $query->orderBy('order')->orderBy('name');
            }])
            ->orderBy('order')
            ->orderBy('name')
            ->get();
    }

    private function accountUsersForAccount($account)
    {
        return $account->users()
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('name')
            ->get();
    }

    private function parseTagNames($value): array
    {
        if (is_array($value)) {
            $names = $value;
        } else {
            $names = preg_split('/,/', (string) $value) ?: [];
        }

        $names = array_map(static fn ($name) => trim((string) $name), $names);
        $names = array_filter($names, static fn ($name) => $name !== '');

        return array_slice(array_values(array_unique($names)), 0, 20);
    }

    private function authorizeTransactionUserScope($account, Request $request, int $userId): void
    {
        if ($userId === $request->user()->id) {
            return;
        }

        $pivot = $account->users()
            ->where('users.id', $request->user()->id)
            ->first()
            ?->pivot;

        $isAccountManager = $pivot && in_array($pivot->role, ['owner', 'admin'], true);
        if (! $isAccountManager) {
            abort(403);
        }
    }
}
