<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\SavingsGoalResource;
use App\Http\Resources\TransactionResource;
use App\Models\SavingsGoal;
use App\Services\AnalyticsService;
use App\Services\SavingsGoalService;
use Carbon\Carbon;
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

        $referenceMonth = $this->resolveReferenceMonth($request);

        $analytics = $this->analyticsService->getDashboardData($account, $request->user(), $referenceMonth);

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
            ->whereBetween('date', [
                $referenceMonth->copy()->startOfMonth()->toDateString(),
                $referenceMonth->copy()->endOfMonth()->toDateString(),
            ])
            ->latest('date')
            ->take(10)
            ->get();

        return response()->json([
            'data' => [
                'analytics' => $analytics,
                'selected_month' => [
                    'value' => $referenceMonth->format('Y-m'),
                    'label' => $referenceMonth->format('F Y'),
                ],
                'recent_transactions' => TransactionResource::collection($recentTransactions)->resolve($request),
                'savings_goals' => SavingsGoalResource::collection($savingsGoals)->resolve($request),
            ],
        ]);
    }

    private function resolveReferenceMonth(Request $request): Carbon
    {
        $currentMonth = now()->startOfMonth();
        $month = (string) $request->query('month', '');

        if ($month === '' || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) {
            return $currentMonth;
        }

        try {
            $referenceMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            return $currentMonth;
        }

        if ($referenceMonth->greaterThan($currentMonth)) {
            return $currentMonth;
        }

        return $referenceMonth;
    }
}
