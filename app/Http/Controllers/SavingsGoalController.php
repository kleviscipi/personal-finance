<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\SavingsGoal;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\SavingsGoalService;
use App\Support\ActiveAccount;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SavingsGoalController extends Controller
{
    public function __construct(
        private SavingsGoalService $savingsGoalService,
        private CurrencyService $currencyService
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $user = $request->user();
        $goals = SavingsGoal::with(['category', 'subcategory', 'user'])
            ->where('account_id', $account->id)
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->latest('target_date')
            ->get()
            ->map(function (SavingsGoal $goal) {
                $progress = $this->savingsGoalService->calculateProgress($goal);
                $projection = $this->savingsGoalService->calculateProjection($goal);

                return [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'target_amount' => $goal->target_amount,
                    'initial_amount' => $goal->initial_amount,
                    'currency' => $goal->currency,
                    'tracking_mode' => $goal->tracking_mode,
                    'start_date' => $goal->start_date,
                    'target_date' => $goal->target_date,
                    'category' => $goal->category,
                    'subcategory' => $goal->subcategory,
                    'user' => $goal->user,
                    'progress' => $progress,
                    'projection' => $projection,
                ];
            });

        return Inertia::render('SavingsGoals/Index', [
            'currentAccount' => $account,
            'goals' => $goals,
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $this->authorize('create', SavingsGoal::class);

        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $categories = $account->categories()
            ->with(['subcategories' => function ($query) {
                $query->orderBy('order')->orderBy('name');
            }])
            ->orderBy('order')
            ->orderBy('name')
            ->get();
        $accountUsers = $account->users()
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('name')
            ->get();

        return Inertia::render('SavingsGoals/Create', [
            'currentAccount' => $account,
            'categories' => $categories,
            'accountUsers' => $accountUsers,
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', SavingsGoal::class);

        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $validated = $this->validatePayload($request, $account->id);
        $validated['user_id'] = $validated['user_id'] ?: null;
        $this->authorizeGoalScope($account, $request->user(), $validated['user_id']);

        SavingsGoal::create([
            'account_id' => $account->id,
            'user_id' => $validated['user_id'],
            'category_id' => $validated['category_id'] ?? null,
            'subcategory_id' => $validated['subcategory_id'] ?? null,
            'name' => $validated['name'],
            'target_amount' => $validated['target_amount'],
            'initial_amount' => $validated['initial_amount'] ?? 0,
            'currency' => $validated['currency'] ?? $account->base_currency,
            'tracking_mode' => $validated['tracking_mode'],
            'start_date' => $validated['start_date'],
            'target_date' => $validated['target_date'],
            'settings' => $validated['settings'] ?? null,
        ]);

        return redirect()
            ->route('savings-goals.index')
            ->with('message', 'Savings goal created successfully.');
    }

    public function edit(Request $request, SavingsGoal $savingsGoal): Response|RedirectResponse
    {
        $this->authorize('update', $savingsGoal);

        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $categories = $account->categories()
            ->with(['subcategories' => function ($query) {
                $query->orderBy('order')->orderBy('name');
            }])
            ->orderBy('order')
            ->orderBy('name')
            ->get();
        $accountUsers = $account->users()
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('name')
            ->get();

        return Inertia::render('SavingsGoals/Edit', [
            'currentAccount' => $account,
            'goal' => $savingsGoal->load(['category', 'subcategory', 'user']),
            'categories' => $categories,
            'accountUsers' => $accountUsers,
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function update(Request $request, SavingsGoal $savingsGoal)
    {
        $this->authorize('update', $savingsGoal);

        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $validated = $this->validatePayload($request, $account->id);
        $validated['user_id'] = $validated['user_id'] ?: null;
        $this->authorizeGoalScope($account, $request->user(), $validated['user_id']);

        $savingsGoal->update([
            'user_id' => $validated['user_id'],
            'category_id' => $validated['category_id'] ?? null,
            'subcategory_id' => $validated['subcategory_id'] ?? null,
            'name' => $validated['name'],
            'target_amount' => $validated['target_amount'],
            'initial_amount' => $validated['initial_amount'] ?? 0,
            'currency' => $validated['currency'] ?? $account->base_currency,
            'tracking_mode' => $validated['tracking_mode'],
            'start_date' => $validated['start_date'],
            'target_date' => $validated['target_date'],
            'settings' => $validated['settings'] ?? null,
        ]);

        return redirect()
            ->route('savings-goals.index')
            ->with('message', 'Savings goal updated successfully.');
    }

    public function destroy(SavingsGoal $savingsGoal)
    {
        $this->authorize('delete', $savingsGoal);

        $savingsGoal->delete();

        return redirect()
            ->route('savings-goals.index')
            ->with('message', 'Savings goal deleted successfully.');
    }

    private function validatePayload(Request $request, int $accountId): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'initial_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(array_keys($this->currencyService->getSupportedCurrencies()))],
            'tracking_mode' => ['required', Rule::in(['net_savings', 'category', 'subcategory', 'manual'])],
            'user_id' => [
                'nullable',
                Rule::exists('account_user', 'user_id')->where(fn ($query) => $query->where('account_id', $accountId)),
            ],
            'category_id' => [
                Rule::requiredIf(fn () => in_array($request->input('tracking_mode'), ['category', 'subcategory'], true)),
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('account_id', $accountId)),
            ],
            'subcategory_id' => [
                Rule::requiredIf(fn () => $request->input('tracking_mode') === 'subcategory'),
                'nullable',
                Rule::exists('subcategories', 'id'),
            ],
            'start_date' => ['required', 'date'],
            'target_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        if (!in_array($validated['tracking_mode'], ['category', 'subcategory'], true)) {
            $validated['category_id'] = null;
            $validated['subcategory_id'] = null;
        }

        if ($validated['tracking_mode'] !== 'subcategory') {
            $validated['subcategory_id'] = null;
        }

        return $validated;
    }

    private function authorizeGoalScope(Account $account, User $user, ?int $userId): void
    {
        if ($userId === null || $userId === $user->id) {
            return;
        }

        $pivot = $account->users()
            ->where('users.id', $user->id)
            ->first()
            ?->pivot;

        $isAccountManager = $pivot && in_array($pivot->role, ['owner', 'admin'], true);
        if (!$isAccountManager) {
            abort(403);
        }
    }
}
