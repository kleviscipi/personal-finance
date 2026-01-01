<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use App\Support\ActiveAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    public function index(Request $request)
    {
        $account = ActiveAccount::resolve($request);
        
        if (!$account) {
            // Redirect to account creation if no account exists
            return redirect()->route('accounts.create');
        }

        $analytics = $this->analyticsService->getDashboardData($account, $request->user());
        
        $recentTransactions = $account->transactions()
            ->with(['category', 'subcategory'])
            ->latest('date')
            ->take(10)
            ->get();

        return Inertia::render('Dashboard', [
            'currentAccount' => $account,
            'analytics' => $analytics,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
