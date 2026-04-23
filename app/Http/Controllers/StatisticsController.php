<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use App\Services\StatisticsExportService;
use App\Support\ActiveAccount;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService,
        private StatisticsExportService $statisticsExportService
    ) {}

    public function index(Request $request): Response
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        [$startDate, $endDate] = $this->resolveDateRange($request);
        $start = $startDate->toDateString();
        $end = $endDate->toDateString();

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

    public function download(Request $request): HttpResponse|RedirectResponse
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        [$startDate, $endDate] = $this->resolveDateRange($request);
        $analytics = $this->analyticsService->getStatisticsRange(
            $account,
            $startDate->toDateString(),
            $endDate->toDateString()
        );

        $contents = $this->statisticsExportService->buildWorkbook($account, $startDate, $endDate, $analytics);
        $filename = $this->statisticsExportService->buildFileName($account, $startDate, $endDate);

        return response($contents, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'public',
        ]);
    }

    private function resolveDateRange(Request $request): array
    {
        $defaultStart = now()->subMonths(5)->startOfMonth();
        $defaultEnd = now()->endOfMonth();

        $startDate = $this->parseDate((string) $request->query('start', ''), $defaultStart);
        $endDate = $this->parseDate((string) $request->query('end', ''), $defaultEnd);

        if ($startDate->greaterThan($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    private function parseDate(string $value, Carbon $fallback): Carbon
    {
        if ($value === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return $fallback->copy();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return $fallback->copy();
        }
    }
}
