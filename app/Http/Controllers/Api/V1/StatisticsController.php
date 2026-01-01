<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class StatisticsController extends ApiController
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    public function index(Request $request)
    {
        $account = $this->resolveAccount($request);

        $start = $request->query('start', now()->subMonths(5)->startOfMonth()->toDateString());
        $end = $request->query('end', now()->endOfMonth()->toDateString());

        $analytics = $this->analyticsService->getStatisticsRange($account, $start, $end);

        return response()->json([
            'data' => [
                'analytics' => $analytics,
                'filters' => [
                    'start' => $start,
                    'end' => $end,
                ],
            ],
        ]);
    }
}
