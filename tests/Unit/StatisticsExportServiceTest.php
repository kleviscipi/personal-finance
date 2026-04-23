<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Services\StatisticsExportService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class StatisticsExportServiceTest extends TestCase
{
    public function test_statistics_export_service_builds_a_workbook_for_the_selected_range(): void
    {
        $service = new StatisticsExportService();
        $account = new Account([
            'name' => 'Default Account',
            'base_currency' => 'ALL',
        ]);
        $startDate = Carbon::parse('2026-01-01');
        $endDate = Carbon::parse('2026-04-30');

        $analytics = [
            'monthly_summary' => [
                [
                    'month' => '2026-01',
                    'income' => '1000.00',
                    'expenses' => '400.00',
                    'transfers' => '100.00',
                    'net' => '600.00',
                ],
                [
                    'month' => '2026-02',
                    'income' => '1200.00',
                    'expenses' => '900.00',
                    'transfers' => '0.00',
                    'net' => '300.00',
                ],
            ],
            'top_categories' => [
                ['category' => 'Transport', 'color' => '#334155', 'total' => '900.00'],
            ],
            'top_subcategories' => [
                [
                    'category' => 'Transport',
                    'subcategory' => 'Moto BMW',
                    'label' => 'Transport • Moto BMW',
                    'color' => '#334155',
                    'total' => '900.00',
                ],
            ],
            'category_mix' => [
                'months' => ['2026-01', '2026-02'],
                'series' => [
                    ['category' => 'Transport', 'values' => ['400.00', '500.00']],
                ],
            ],
            'subcategory_mix' => [
                'months' => ['2026-01', '2026-02'],
                'series' => [
                    ['label' => 'Transport • Moto BMW', 'values' => ['400.00', '500.00']],
                ],
            ],
            'expense_share' => [
                'months' => ['2026-01', '2026-02'],
                'series' => [
                    ['category' => 'Transport', 'values' => ['100.00', '55.56']],
                ],
            ],
            'median_expense' => '650.00',
            'missing_rates' => [
                'count' => 1,
                'currencies' => ['EUR'],
            ],
            'totals' => [
                'income' => '2200.00',
                'expenses' => '1300.00',
                'transfers' => '100.00',
                'net' => '900.00',
                'opening_balance' => '5000.00',
                'net_with_opening' => '5900.00',
                'net_with_opening_conversions' => [
                    ['amount' => '61.78', 'currency' => 'EUR', 'rate_date' => '2026-04-30'],
                ],
            ],
        ];

        $contents = $service->buildWorkbook($account, $startDate, $endDate, $analytics);

        $this->assertStringContainsString('Worksheet ss:Name="Summary"', $contents);
        $this->assertStringContainsString('Worksheet ss:Name="Monthly Breakdown"', $contents);
        $this->assertStringContainsString('Worksheet ss:Name="Top Categories"', $contents);
        $this->assertStringContainsString('Worksheet ss:Name="Expense Share"', $contents);
        $this->assertStringContainsString('2026-01-01', $contents);
        $this->assertStringContainsString('2026-04-30', $contents);
        $this->assertStringContainsString('Transport', $contents);
        $this->assertStringContainsString('Transport • Moto BMW', $contents);
        $this->assertSame(
            'default-account-statistics-2026-01-01-to-2026-04-30.xls',
            $service->buildFileName($account, $startDate, $endDate)
        );
    }
}
