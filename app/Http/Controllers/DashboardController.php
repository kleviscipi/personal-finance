<?php

namespace App\Http\Controllers;

use App\Models\SavingsGoal;
use App\Services\AnalyticsService;
use App\Services\SavingsGoalService;
use App\Support\ActiveAccount;
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
            ->latest('date')
            ->take(10)
            ->get();

        return Inertia::render('Dashboard', [
            'currentAccount' => $account,
            'analytics' => $analytics,
            'recentTransactions' => $recentTransactions,
            'savingsGoals' => $savingsGoals,
        ]);
    }
}
