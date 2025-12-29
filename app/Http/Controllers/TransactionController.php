<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    public function index(Request $request)
    {
        $account = $request->user()->accounts()->first();
        if (!$account) {
            return redirect()->route('accounts.create');
        }
        
        $query = $account->transactions()->with(['category', 'subcategory', 'creator']);
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }
        
        $transactions = $query->latest('date')->paginate(15);
        $categories = $account->categories()->orderBy('order')->orderBy('name')->get();
        
        return Inertia::render('Transactions/Index', [
            'currentAccount' => $account,
            'transactions' => $transactions,
            'categories' => $categories,
            'filters' => $request->only(['type', 'category_id', 'date_from', 'date_to']),
        ]);
    }

    public function create()
    {
        $account = auth()->user()->accounts()->first();
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
        
        return Inertia::render('Transactions/Create', [
            'currentAccount' => $account,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:expense,income,transfer',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,EUR,ALL',
            'date' => 'required|date',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'description' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|string|max:255',
        ]);

        $account = $request->user()->accounts()->first();
        $this->transactionService->createTransaction($account, $request->user(), $validated);

        return redirect()->route('transactions.index')->with('message', 'Transaction created successfully.');
    }

    public function edit(Transaction $transaction)
    {
        $this->authorize('update', $transaction);
        
        $account = auth()->user()->accounts()->first();
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
        
        return Inertia::render('Transactions/Edit', [
            'currentAccount' => $account,
            'transaction' => $transaction->load(['category', 'subcategory']),
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $validated = $request->validate([
            'type' => 'required|in:expense,income,transfer',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,EUR,ALL',
            'date' => 'required|date',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'description' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|string|max:255',
        ]);

        $this->transactionService->updateTransaction($transaction, $request->user(), $validated);

        return redirect()->route('transactions.index')->with('message', 'Transaction updated successfully.');
    }

    public function destroy(Transaction $transaction)
    {
        $this->authorize('delete', $transaction);
        
        $this->transactionService->deleteTransaction($transaction, auth()->user());

        return redirect()->route('transactions.index')->with('message', 'Transaction deleted successfully.');
    }
}
