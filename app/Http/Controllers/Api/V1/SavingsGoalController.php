<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\SavingsGoalResource;
use App\Models\SavingsGoal;
use App\Services\CurrencyService;
use App\Services\SavingsGoalService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SavingsGoalController extends ApiController
{
    public function __construct(
        private SavingsGoalService $savingsGoalService,
        private CurrencyService $currencyService
    ) {}

    public function index(Request $request)
    {
        $account = $this->resolveAccount($request);
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
                $goal->setAttribute('progress', $this->savingsGoalService->calculateProgress($goal));
                $goal->setAttribute('projection', $this->savingsGoalService->calculateProjection($goal));
                return $goal;
            });

        return SavingsGoalResource::collection($goals);
    }

    public function show(Request $request, int $savingsGoalId)
    {
        $account = $this->resolveAccount($request);

        $goal = SavingsGoal::with(['category', 'subcategory', 'user'])
            ->where('account_id', $account->id)
            ->findOrFail($savingsGoalId);

        $this->authorizeGoalVisibility($account, $request->user(), $goal);

        $goal->setAttribute('progress', $this->savingsGoalService->calculateProgress($goal));
        $goal->setAttribute('projection', $this->savingsGoalService->calculateProjection($goal));

        return new SavingsGoalResource($goal);
    }

    public function store(Request $request)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin', 'member']);

        $validated = $this->validatePayload($request, $account->id);
        $validated['user_id'] = $validated['user_id'] ?: null;
        $this->authorizeGoalScope($account, $request->user(), $validated['user_id']);

        $goal = SavingsGoal::create([
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

        return (new SavingsGoalResource($goal->load(['category', 'subcategory', 'user'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, int $savingsGoalId)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin', 'member']);

        $goal = SavingsGoal::where('account_id', $account->id)->findOrFail($savingsGoalId);

        $this->authorizeGoalVisibility($account, $request->user(), $goal);

        $validated = $this->validatePayload($request, $account->id);
        $validated['user_id'] = $validated['user_id'] ?: null;
        $this->authorizeGoalScope($account, $request->user(), $validated['user_id']);

        $goal->update([
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

        return new SavingsGoalResource($goal->load(['category', 'subcategory', 'user']));
    }

    public function destroy(Request $request, int $savingsGoalId)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin', 'member']);

        $goal = SavingsGoal::where('account_id', $account->id)->findOrFail($savingsGoalId);

        $this->authorizeGoalVisibility($account, $request->user(), $goal);

        $goal->delete();

        return response()->json([], 204);
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

    private function authorizeGoalScope($account, $user, ?int $userId): void
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

    private function authorizeGoalVisibility($account, $user, SavingsGoal $goal): void
    {
        if ($goal->user_id === null || $goal->user_id === $user->id) {
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
