<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Services\BudgetService;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BudgetController extends ApiController
{
    public function __construct(
        private BudgetService $budgetService,
        private CurrencyService $currencyService
    ) {}

    public function index(Request $request)
    {
        $account = $this->resolveAccount($request);
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
                $budget->setAttribute('progress', $this->budgetService->calculateBudgetProgress($budget));
                return $budget;
            });

        return BudgetResource::collection($budgets);
    }

    public function show(Request $request, int $budgetId)
    {
        $account = $this->resolveAccount($request);

        $budget = Budget::with(['category', 'subcategory', 'user'])
            ->where('account_id', $account->id)
            ->findOrFail($budgetId);

        $this->authorizeBudgetVisibility($account, $request->user(), $budget);

        $budget->setAttribute('progress', $this->budgetService->calculateBudgetProgress($budget));

        return new BudgetResource($budget);
    }

    public function store(Request $request)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin', 'member']);

        $validated = $request->validate([
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('account_id', $account->id)),
            ],
            'subcategory_id' => ['nullable', Rule::exists('subcategories', 'id')],
            'user_id' => [
                'nullable',
                Rule::exists('account_user', 'user_id')->where(fn ($query) => $query->where('account_id', $account->id)),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(array_keys($this->currencyService->getSupportedCurrencies()))],
            'period' => ['required', Rule::in(['monthly', 'yearly'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $validated['user_id'] = $validated['user_id'] ?? null;
        $this->authorizeBudgetScope($account, $request->user(), $validated['user_id']);

        $budget = $this->budgetService->createBudget($account, $validated);

        return (new BudgetResource($budget->load(['category', 'subcategory', 'user'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, int $budgetId)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin', 'member']);

        $budget = Budget::where('account_id', $account->id)->findOrFail($budgetId);

        $this->authorizeBudgetVisibility($account, $request->user(), $budget);

        $validated = $request->validate([
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('account_id', $account->id)),
            ],
            'subcategory_id' => ['nullable', Rule::exists('subcategories', 'id')],
            'user_id' => [
                'nullable',
                Rule::exists('account_user', 'user_id')->where(fn ($query) => $query->where('account_id', $account->id)),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(array_keys($this->currencyService->getSupportedCurrencies()))],
            'period' => ['required', Rule::in(['monthly', 'yearly'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $validated['user_id'] = $validated['user_id'] ?? null;
        $this->authorizeBudgetScope($account, $request->user(), $validated['user_id']);

        $budget = $this->budgetService->updateBudget($budget, $validated);

        return new BudgetResource($budget->load(['category', 'subcategory', 'user']));
    }

    public function destroy(Request $request, int $budgetId)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin', 'member']);

        $budget = Budget::where('account_id', $account->id)->findOrFail($budgetId);

        $this->authorizeBudgetVisibility($account, $request->user(), $budget);

        $this->budgetService->deleteBudget($budget);

        return response()->json([], 204);
    }

    private function authorizeBudgetScope($account, $user, ?int $userId): void
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

    private function authorizeBudgetVisibility($account, $user, Budget $budget): void
    {
        if ($budget->user_id === null || $budget->user_id === $user->id) {
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
