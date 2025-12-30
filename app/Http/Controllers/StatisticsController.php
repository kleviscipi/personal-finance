<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use App\Support\ActiveAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    public function index(Request $request): Response
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $start = $request->query('start', now()->subMonths(5)->startOfMonth()->toDateString());
        $end = $request->query('end', now()->endOfMonth()->toDateString());

        $analytics = $this->analyticsService->getStatisticsRange($account, $start, $end);

        return Inertia::render('Statistics/Index', [
            'currentAccount' => $account,
            'analytics' => $analytics,
            'filters' => [
                'start' => $start,
                'end' => $end,
            ],
        ]);
    }
}
