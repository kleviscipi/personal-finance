<?php

namespace App\Http\Controllers;

use App\Models\SavingsGoal;
use App\Services\AnalyticsService;
use App\Services\SavingsGoalService;
use App\Support\ActiveAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService,
        private SavingsGoalService $savingsGoalService
    ) {}

    public function index(Request $request)
    {
        $account = ActiveAccount::resolve($request);
        
        if (!$account) {
            // Redirect to account creation if no account exists
            return redirect()->route('accounts.create');
        }

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
                $progress = $this->savingsGoalService->calculateProgress($goal);

                return [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'target_amount' => $goal->target_amount,
                    'currency' => $goal->currency,
                    'target_date' => $goal->target_date,
                    'tracking_mode' => $goal->tracking_mode,
                    'category' => $goal->category,
                    'subcategory' => $goal->subcategory,
                    'user' => $goal->user,
                    'progress' => $progress,
                ];
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

        return Inertia::render('Dashboard', [
            'currentAccount' => $account,
            'analytics' => $analytics,
            'recentTransactions' => $recentTransactions,
            'savingsGoals' => $savingsGoals,
            'selectedMonth' => $this->buildSelectedMonthData($referenceMonth),
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

    private function buildSelectedMonthData(Carbon $referenceMonth): array
    {
        $currentMonth = now()->startOfMonth();

        return [
            'value' => $referenceMonth->format('Y-m'),
            'label' => $referenceMonth->format('F Y'),
            'short_label' => $referenceMonth->format('M Y'),
            'previous' => $referenceMonth->copy()->subMonthNoOverflow()->format('Y-m'),
            'next' => $referenceMonth->copy()->addMonthNoOverflow()->format('Y-m'),
            'current' => $currentMonth->format('Y-m'),
            'is_current' => $referenceMonth->equalTo($currentMonth),
            'can_go_next' => $referenceMonth->lessThan($currentMonth),
        ];
    }
}
