<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\SavingsGoalResource;
use App\Http\Resources\TransactionResource;
use App\Models\SavingsGoal;
use App\Services\AnalyticsService;
use App\Services\SavingsGoalService;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function __construct(
        private AnalyticsService $analyticsService,
        private SavingsGoalService $savingsGoalService
    ) {}

    public function show(Request $request)
    {
        $account = $this->resolveAccount($request);

        $analytics = $this->analyticsService->getDashboardData($account, $request->user());

        $savingsGoals = SavingsGoal::with(['category', 'subcategory', 'user'])
            ->where('account_id', $account->id)
            ->where(function ($query) use ($request) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $request->user()->id);
            })
            ->latest('target_date')
            ->take(3)
            ->get()
            ->map(function (SavingsGoal $goal) {
                $goal->setAttribute('progress', $this->savingsGoalService->calculateProgress($goal));
                return $goal;
            });

        $recentTransactions = $account->transactions()
            ->with(['category', 'subcategory'])
            ->latest('date')
            ->take(10)
            ->get();

        return response()->json([
            'data' => [
                'analytics' => $analytics,
                'recent_transactions' => TransactionResource::collection($recentTransactions)->resolve($request),
                'savings_goals' => SavingsGoalResource::collection($savingsGoals)->resolve($request),
            ],
        ]);
    }
}
