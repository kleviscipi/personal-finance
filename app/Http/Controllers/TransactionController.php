<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\CurrencyService;
use App\Services\TransactionService;
use App\Support\ActiveAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService,
        private CurrencyService $currencyService
    ) {}

    public function index(Request $request)
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }
        
        $query = $account->transactions()->with(['category', 'subcategory', 'creator', 'latestHistory.user', 'tags']);
        
        if ($request->filled('q')) {
            $search = mb_strtolower($request->input('q'));
            $like = '%' . $search . '%';
            $query->where(function ($query) use ($like) {
                $query->whereRaw('LOWER(description) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(payment_method) LIKE ?', [$like])
                    ->orWhereHas('category', function ($query) use ($like) {
                        $query->whereRaw('LOWER(name) LIKE ?', [$like]);
                    })
                    ->orWhereHas('subcategory', function ($query) use ($like) {
                        $query->whereRaw('LOWER(name) LIKE ?', [$like]);
                    })
                    ->orWhereHas('creator', function ($query) use ($like) {
                        $query->whereRaw('LOWER(name) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                    });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }
        
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }

        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', $request->amount_max);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        $tagIds = array_filter((array) $request->input('tag_ids', []), static fn ($id) => $id !== null && $id !== '');
        if (!empty($tagIds)) {
            $query->whereHas('tags', function ($query) use ($tagIds) {
                $query->whereIn('tags.id', $tagIds);
            });
        }
        
        $transactions = $query->latest('date')->paginate(15);
        $categories = $account->categories()
            ->with(['subcategories' => function ($query) {
                $query->orderBy('order')->orderBy('name');
            }])
            ->orderBy('order')
            ->orderBy('name')
            ->get();
        $tags = $account->tags()->orderBy('name')->get();
        $accountUsers = $account->users()
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('name')
            ->get();
        
        return Inertia::render('Transactions/Index', [
            'currentAccount' => $account,
            'transactions' => $transactions,
            'categories' => $categories,
            'tags' => $tags,
            'accountUsers' => $accountUsers,
            'filters' => $request->only([
                'q',
                'type',
                'category_id',
                'subcategory_id',
                'date_from',
                'date_to',
                'amount_min',
                'amount_max',
                'payment_method',
                'created_by',
                'tag_ids',
            ]),
        ]);
    }

    public function create()
    {
        $account = ActiveAccount::resolve(request());
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
        $tags = $account->tags()->orderBy('name')->get();
        
        return Inertia::render('Transactions/Create', [
            'currentAccount' => $account,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function store(Request $request)
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $validated = $request->validate([
            'type' => 'required|in:expense,income,transfer',
            'amount' => 'required|numeric|min:0',
            'currency' => ['required', Rule::in(array_keys($this->currencyService->getSupportedCurrencies()))],
            'date' => 'required|date',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'description' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|string|max:255',
            'tag_ids' => ['array'],
            'tag_ids.*' => [
                Rule::exists('tags', 'id')->where(fn ($query) => $query->where('account_id', $account?->id)),
            ],
            'tag_names' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['tag_ids'] = $validated['tag_ids'] ?? [];
        $validated['tag_names'] = $this->parseTagNames($request->input('tag_names'));
        $this->transactionService->createTransaction($account, $request->user(), $validated);

        return redirect()->route('transactions.index')->with('message', 'Transaction created successfully.');
    }

    public function edit(Transaction $transaction)
    {
        $this->authorize('update', $transaction);
        
        $account = ActiveAccount::resolve(request());
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
        $tags = $account->tags()->orderBy('name')->get();
        
        return Inertia::render('Transactions/Edit', [
            'currentAccount' => $account,
            'transaction' => $transaction->load([
                'category',
                'subcategory',
                'histories' => function ($query) {
                    $query->with('user')->latest('created_at');
                },
                'tags',
            ]),
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $validated = $request->validate([
            'type' => 'required|in:expense,income,transfer',
            'amount' => 'required|numeric|min:0',
            'currency' => ['required', Rule::in(array_keys($this->currencyService->getSupportedCurrencies()))],
            'date' => 'required|date',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'description' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|string|max:255',
            'tag_ids' => ['array'],
            'tag_ids.*' => [
                Rule::exists('tags', 'id')->where(fn ($query) => $query->where('account_id', $account?->id)),
            ],
            'tag_names' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['tag_ids'] = $validated['tag_ids'] ?? [];
        $validated['tag_names'] = $this->parseTagNames($request->input('tag_names'));

        $this->transactionService->updateTransaction($transaction, $request->user(), $validated);

        return redirect()->route('transactions.index')->with('message', 'Transaction updated successfully.');
    }

    public function destroy(Transaction $transaction)
    {
        $this->authorize('delete', $transaction);
        
        $this->transactionService->deleteTransaction($transaction, auth()->user());

        return redirect()->route('transactions.index')->with('message', 'Transaction deleted successfully.');
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
}
