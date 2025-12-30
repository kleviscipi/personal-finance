<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Services\BudgetService;
use App\Support\ActiveAccount;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(
        private BudgetService $budgetService
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $budgets = Budget::with(['category', 'subcategory'])
            ->where('account_id', $account->id)
            ->latest('start_date')
            ->get();

        return Inertia::render('Budgets/Index', [
            'budgets' => $budgets,
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $this->authorize('create', Budget::class);

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

        return Inertia::render('Budgets/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Budget::class);

        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $validated = $request->validate([
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('account_id', $account?->id)),
            ],
            'subcategory_id' => [
                'nullable',
                Rule::exists('subcategories', 'id'),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(['USD', 'EUR', 'ALL'])],
            'period' => ['required', Rule::in(['monthly', 'yearly'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $this->budgetService->createBudget($account, $validated);

        return redirect()
            ->route('budgets.index')
            ->with('message', 'Budget created successfully.');
    }

    public function edit(Request $request, Budget $budget): Response|RedirectResponse
    {
        $this->authorize('update', $budget);

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

        return Inertia::render('Budgets/Edit', [
            'budget' => $budget->load(['category', 'subcategory']),
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Budget $budget)
    {
        $this->authorize('update', $budget);

        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $validated = $request->validate([
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('account_id', $account?->id)),
            ],
            'subcategory_id' => [
                'nullable',
                Rule::exists('subcategories', 'id'),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(['USD', 'EUR', 'ALL'])],
            'period' => ['required', Rule::in(['monthly', 'yearly'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $this->budgetService->updateBudget($budget, $validated);

        return redirect()
            ->route('budgets.index')
            ->with('message', 'Budget updated successfully.');
    }

    public function destroy(Budget $budget)
    {
        $this->authorize('delete', $budget);

        $this->budgetService->deleteBudget($budget);

        return redirect()
            ->route('budgets.index')
            ->with('message', 'Budget deleted successfully.');
    }
}
