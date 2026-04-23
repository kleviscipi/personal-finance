<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\StatisticsExportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StatisticsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistics_export_downloads_an_excel_workbook_for_the_selected_date_range(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'name' => 'Default Account',
            'base_currency' => 'USD',
            'description' => 'Primary account',
            'is_active' => true,
        ]);

        $user->accounts()->attach($account->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $analytics = [
            'monthly_summary' => [],
            'top_categories' => [],
            'top_subcategories' => [],
            'category_mix' => ['months' => [], 'series' => []],
            'subcategory_mix' => ['months' => [], 'series' => []],
            'expense_share' => ['months' => [], 'series' => []],
            'missing_rates' => ['count' => 0, 'currencies' => []],
            'totals' => [
                'income' => 0,
                'expenses' => 0,
                'transfers' => 0,
                'net' => 0,
                'opening_balance' => 0,
                'net_with_opening' => 0,
                'net_with_opening_conversions' => [],
            ],
        ];

        $analyticsService = Mockery::mock(AnalyticsService::class);
        $analyticsService->shouldReceive('getStatisticsRange')
            ->once()
            ->withArgs(function ($resolvedAccount, $start, $end) use ($account) {
                return $resolvedAccount->is($account)
                    && $start === '2026-01-01'
                    && $end === '2026-04-30';
            })
            ->andReturn($analytics);
        $this->app->instance(AnalyticsService::class, $analyticsService);

        $exportService = Mockery::mock(StatisticsExportService::class);
        $exportService->shouldReceive('buildWorkbook')
            ->once()
            ->withArgs(function ($resolvedAccount, $startDate, $endDate, $resolvedAnalytics) use ($account, $analytics) {
                return $resolvedAccount->is($account)
                    && $startDate instanceof Carbon
                    && $endDate instanceof Carbon
                    && $startDate->toDateString() === '2026-01-01'
                    && $endDate->toDateString() === '2026-04-30'
                    && $resolvedAnalytics === $analytics;
            })
            ->andReturn('<Workbook />');
        $exportService->shouldReceive('buildFileName')
            ->once()
            ->withArgs(function ($resolvedAccount, $startDate, $endDate) use ($account) {
                return $resolvedAccount->is($account)
                    && $startDate instanceof Carbon
                    && $endDate instanceof Carbon
                    && $startDate->toDateString() === '2026-01-01'
                    && $endDate->toDateString() === '2026-04-30';
            })
            ->andReturn('default-account-statistics-2026-01-01-to-2026-04-30.xls');
        $this->app->instance(StatisticsExportService::class, $exportService);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_account_id' => $account->id])
            ->get(route('statistics.export', [
                'start' => '2026-01-01',
                'end' => '2026-04-30',
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertHeader(
            'content-disposition',
            'attachment; filename="default-account-statistics-2026-01-01-to-2026-04-30.xls"'
        );
        $response->assertSee('<Workbook />', false);
    }
}
