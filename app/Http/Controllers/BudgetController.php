<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Budget;
use App\Models\User;
use App\Services\BudgetService;
use App\Services\CurrencyService;
use App\Support\ActiveAccount;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(
        private BudgetService $budgetService,
        private CurrencyService $currencyService
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $user = $request->user();
        $budgets = Budget::with(['category', 'subcategory', 'user'])
            ->where('account_id', $account->id)
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->latest('start_date')
            ->get()
            ->map(function (Budget $budget) {
                $progress = $this->budgetService->calculateBudgetProgress($budget);
                $budget->setAttribute('progress', $progress);
                return $budget;
            });

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
        $accountUsers = $account->users()
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Budgets/Create', [
            'categories' => $categories,
            'accountUsers' => $accountUsers,
            'currentUserId' => $request->user()->id,
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
            'user_id' => [
                'nullable',
                Rule::exists('account_user', 'user_id')->where(fn ($query) => $query->where('account_id', $account?->id)),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(array_keys($this->currencyService->getSupportedCurrencies()))],
            'period' => ['required', Rule::in(['monthly', 'yearly'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $validated['user_id'] = $validated['user_id'] ?: null;
        $this->authorizeBudgetScope($account, $request->user(), $validated['user_id']);

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
        $accountUsers = $account->users()
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Budgets/Edit', [
            'budget' => $budget->load(['category', 'subcategory']),
            'categories' => $categories,
            'accountUsers' => $accountUsers,
            'currentUserId' => $request->user()->id,
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
            'user_id' => [
                'nullable',
                Rule::exists('account_user', 'user_id')->where(fn ($query) => $query->where('account_id', $account?->id)),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(array_keys($this->currencyService->getSupportedCurrencies()))],
            'period' => ['required', Rule::in(['monthly', 'yearly'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $validated['user_id'] = $validated['user_id'] ?: null;
        $this->authorizeBudgetScope($account, $request->user(), $validated['user_id']);

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

    private function authorizeBudgetScope(Account $account, User $user, ?int $userId): void
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
