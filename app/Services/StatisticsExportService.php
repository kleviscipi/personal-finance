<?php

namespace App\Services;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Support\Str;
use XMLWriter;

class StatisticsExportService
{
    private const SPREADSHEET_NS = 'urn:schemas-microsoft-com:office:spreadsheet';

    private const EXCEL_NS = 'urn:schemas-microsoft-com:office:excel';

    public function buildWorkbook(Account $account, Carbon $startDate, Carbon $endDate, array $analytics): string
    {
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->writePI('mso-application', 'progid="Excel.Sheet"');

        $writer->startElementNS(null, 'Workbook', self::SPREADSHEET_NS);
        $writer->writeAttributeNs('xmlns', 'o', null, 'urn:schemas-microsoft-com:office:office');
        $writer->writeAttributeNs('xmlns', 'x', null, self::EXCEL_NS);
        $writer->writeAttributeNs('xmlns', 'ss', null, self::SPREADSHEET_NS);
        $writer->writeAttributeNs('xmlns', 'html', null, 'http://www.w3.org/TR/REC-html40');

        $this->writeStyles($writer);

        $baseCurrency = $account->base_currency;

        $this->writeWorksheet(
            $writer,
            'Summary',
            ['Theme', 'Metric', 'Value', 'Unit', 'Comment'],
            $this->buildSummaryRows($account, $startDate, $endDate, $analytics),
            sprintf('%s Statistics Export', $account->name),
            sprintf(
                'Date range: %s to %s | Base currency: %s | Exported %s',
                $startDate->toDateString(),
                $endDate->toDateString(),
                $baseCurrency,
                now()->format('Y-m-d H:i')
            ),
            [100, 190, 120, 85, 320]
        );

        $this->writeWorksheet(
            $writer,
            'Monthly Breakdown',
            [
                'Month',
                sprintf('Income (%s)', $baseCurrency),
                sprintf('Expenses (%s)', $baseCurrency),
                sprintf('Transfers (%s)', $baseCurrency),
                sprintf('Net (%s)', $baseCurrency),
            ],
            $this->buildMonthlyRows($analytics),
            'Monthly Breakdown',
            'Monthly statistics for the selected range. Opening balance rows are excluded from income and expense totals.',
            [90, 120, 120, 120, 120]
        );

        $this->writeWorksheet(
            $writer,
            'Top Categories',
            [
                'Rank',
                'Category',
                sprintf('Total (%s)', $baseCurrency),
                'Share %',
                'Color',
            ],
            $this->buildTopCategoryRows($analytics),
            'Top Categories',
            'Largest expense categories in the selected date range.',
            [60, 170, 120, 90, 90]
        );

        $this->writeWorksheet(
            $writer,
            'Top Subcategories',
            [
                'Rank',
                'Category',
                'Subcategory',
                'Label',
                sprintf('Total (%s)', $baseCurrency),
                'Share %',
            ],
            $this->buildTopSubcategoryRows($analytics),
            'Top Subcategories',
            'Largest expense subcategories in the selected date range.',
            [60, 140, 140, 200, 120, 90]
        );

        $categoryMix = $analytics['category_mix'] ?? [];
        $categoryMonths = $categoryMix['months'] ?? [];
        $this->writeWorksheet(
            $writer,
            'Category Mix',
            array_merge(['Category'], $categoryMonths),
            $this->buildSeriesRows($categoryMix['series'] ?? [], 'category', false, count($categoryMonths) + 1),
            'Category Mix by Month',
            'Monthly expense totals for top categories.',
            array_merge([180], array_fill(0, count($categoryMonths), 95))
        );

        $subcategoryMix = $analytics['subcategory_mix'] ?? [];
        $subcategoryMonths = $subcategoryMix['months'] ?? [];
        $this->writeWorksheet(
            $writer,
            'Subcategory Mix',
            array_merge(['Subcategory'], $subcategoryMonths),
            $this->buildSeriesRows($subcategoryMix['series'] ?? [], 'label', false, count($subcategoryMonths) + 1),
            'Subcategory Mix by Month',
            'Monthly expense totals for top subcategories.',
            array_merge([220], array_fill(0, count($subcategoryMonths), 95))
        );

        $expenseShare = $analytics['expense_share'] ?? [];
        $expenseShareMonths = $expenseShare['months'] ?? [];
        $this->writeWorksheet(
            $writer,
            'Expense Share',
            array_merge(['Category'], $expenseShareMonths),
            $this->buildSeriesRows($expenseShare['series'] ?? [], 'category', true, count($expenseShareMonths) + 1),
            'Expense Share by Month',
            'Each category shown as a percentage of monthly expenses.',
            array_merge([180], array_fill(0, count($expenseShareMonths), 95))
        );

        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    public function buildFileName(Account $account, Carbon $startDate, Carbon $endDate): string
    {
        $slug = trim((string) Str::of($account->name)->slug('-'));

        return sprintf(
            '%s-statistics-%s-to-%s.xls',
            $slug !== '' ? $slug : 'account',
            $startDate->toDateString(),
            $endDate->toDateString()
        );
    }

    private function buildSummaryRows(Account $account, Carbon $startDate, Carbon $endDate, array $analytics): array
    {
        $totals = $analytics['totals'] ?? [];
        $missingRates = $analytics['missing_rates'] ?? [];
        $monthlySummary = $analytics['monthly_summary'] ?? [];
        $topCategories = $analytics['top_categories'] ?? [];
        $topSubcategories = $analytics['top_subcategories'] ?? [];

        $rows = [];

        $rows[] = $this->sectionRow('Date Range', 5);
        $rows[] = $this->metricRow('Range', 'Start Date', $this->stringCell($startDate->toDateString(), 'Text'));
        $rows[] = $this->metricRow('Range', 'End Date', $this->stringCell($endDate->toDateString(), 'Text'));
        $rows[] = $this->metricRow(
            'Range',
            'Months Included',
            $this->numberCell(count($monthlySummary), 'Integer'),
            '',
            'Monthly breakdown includes one row per calendar month touched by the selected range.'
        );
        $rows[] = $this->metricRow(
            'Range',
            'Base Currency',
            $this->stringCell($account->base_currency, 'BadgeShared'),
            '',
            'All exported statistics are normalized to this currency.'
        );

        $rows[] = $this->blankRow(5);
        $rows[] = $this->sectionRow('Totals', 5);
        $rows[] = $this->metricRow(
            'Totals',
            'Income',
            $this->amountCell($totals['income'] ?? 0, 'Income', 'Expense', 'AmountNeutral'),
            $account->base_currency,
            'Opening balance rows are excluded.'
        );
        $rows[] = $this->metricRow(
            'Totals',
            'Expenses',
            $this->amountCell($totals['expenses'] ?? 0, 'AmountPositive', 'Expense', 'AmountNeutral'),
            $account->base_currency,
            'All expense transactions in the selected range.'
        );
        $rows[] = $this->metricRow(
            'Totals',
            'Transfers',
            $this->amountCell($totals['transfers'] ?? 0, 'AmountPositive', 'AmountNegative', 'AmountNeutral'),
            $account->base_currency,
            'Transfers are tracked separately from net income and expenses.'
        );
        $rows[] = $this->metricRow(
            'Totals',
            'Opening Balance',
            $this->amountCell($totals['opening_balance'] ?? 0),
            $account->base_currency,
            'Opening balance rows that fall inside the selected range.'
        );
        $rows[] = $this->metricRow(
            'Totals',
            'Net Balance',
            $this->amountCell($totals['net'] ?? 0),
            $account->base_currency,
            'Income minus expenses, excluding opening balance rows.'
        );
        $rows[] = $this->metricRow(
            'Totals',
            'Net incl. Opening Balance',
            $this->amountCell($totals['net_with_opening'] ?? 0),
            $account->base_currency,
            'Net balance plus opening balance rows inside the selected range.'
        );
        $rows[] = $this->metricRow(
            'Totals',
            'Median Monthly Expense',
            $this->amountCell($analytics['median_expense'] ?? 0, 'AmountPositive', 'Expense', 'AmountNeutral'),
            $account->base_currency,
            'Median of the monthly expense totals shown in the monthly breakdown sheet.'
        );
        $rows[] = $this->metricRow(
            'Totals',
            'Net Conversions',
            $this->stringCell($this->formatConversions($totals['net_with_opening_conversions'] ?? []), 'TextWrap'),
            '',
            'Latest available FX conversions for net incl. opening balance.'
        );

        $rows[] = $this->blankRow(5);
        $rows[] = $this->sectionRow('Coverage', 5);
        $rows[] = $this->metricRow(
            'Coverage',
            'Top Categories',
            $this->numberCell(count($topCategories), 'Integer'),
            '',
            'Largest expense categories included in the export.'
        );
        $rows[] = $this->metricRow(
            'Coverage',
            'Top Subcategories',
            $this->numberCell(count($topSubcategories), 'Integer'),
            '',
            'Largest expense subcategories included in the export.'
        );
        $rows[] = $this->metricRow(
            'Coverage',
            'Missing FX Transactions',
            $this->numberCell($missingRates['count'] ?? 0, 'Integer'),
            '',
            ! empty($missingRates['currencies'])
                ? 'Currencies affected: '.implode(', ', $missingRates['currencies'])
                : 'No FX gaps detected for the selected range.'
        );

        return $rows;
    }

    private function buildMonthlyRows(array $analytics): array
    {
        $rows = $analytics['monthly_summary'] ?? [];

        if ($rows === []) {
            return [$this->emptyStateRow('No monthly statistics available for this range.', 5)];
        }

        return array_map(function (array $row) {
            return [
                $this->stringCell($row['month'] ?? '', 'Text'),
                $this->numberCell($row['income'] ?? 0, 'Income'),
                $this->numberCell($row['expenses'] ?? 0, 'Expense'),
                $this->numberCell($row['transfers'] ?? 0, 'AmountNeutral'),
                $this->amountCell($row['net'] ?? 0),
            ];
        }, $rows);
    }

    private function buildTopCategoryRows(array $analytics): array
    {
        $rows = $analytics['top_categories'] ?? [];
        $totalExpenses = (float) ($analytics['totals']['expenses'] ?? 0);

        if ($rows === []) {
            return [$this->emptyStateRow('No expense category data for this range.', 5)];
        }

        $result = [];
        foreach (array_values($rows) as $index => $row) {
            $total = data_get($row, 'total', 0);
            $share = $totalExpenses > 0 ? round((((float) $total) / $totalExpenses) * 100, 2) : 0;

            $result[] = [
                $this->numberCell($index + 1, 'Integer'),
                $this->stringCell((string) data_get($row, 'category', ''), 'Text'),
                $this->amountCell($total, 'Expense', 'Expense', 'AmountNeutral'),
                $this->numberCell($share, 'Percent'),
                $this->stringCell((string) data_get($row, 'color', ''), 'MutedText'),
            ];
        }

        return $result;
    }

    private function buildTopSubcategoryRows(array $analytics): array
    {
        $rows = $analytics['top_subcategories'] ?? [];
        $totalExpenses = (float) ($analytics['totals']['expenses'] ?? 0);

        if ($rows === []) {
            return [$this->emptyStateRow('No expense subcategory data for this range.', 6)];
        }

        $result = [];
        foreach (array_values($rows) as $index => $row) {
            $total = data_get($row, 'total', 0);
            $share = $totalExpenses > 0 ? round((((float) $total) / $totalExpenses) * 100, 2) : 0;

            $result[] = [
                $this->numberCell($index + 1, 'Integer'),
                $this->stringCell((string) data_get($row, 'category', ''), 'Text'),
                $this->stringCell((string) data_get($row, 'subcategory', ''), 'Text'),
                $this->stringCell((string) data_get($row, 'label', ''), 'TextWrap'),
                $this->amountCell($total, 'Expense', 'Expense', 'AmountNeutral'),
                $this->numberCell($share, 'Percent'),
            ];
        }

        return $result;
    }

    private function buildSeriesRows(
        array $seriesRows,
        string $labelKey,
        bool $isPercentage = false,
        int $columnCount = 2
    ): array
    {
        if ($seriesRows === []) {
            return [$this->emptyStateRow('No monthly series data for this range.', $columnCount)];
        }

        $rows = [];

        foreach ($seriesRows as $series) {
            $label = $series[$labelKey] ?? '';
            $values = $series['values'] ?? [];

            $row = [$this->stringCell($label, 'TextWrap')];
            foreach ($values as $value) {
                $row[] = $isPercentage
                    ? $this->numberCell($value, 'Percent')
                    : $this->amountCell($value, 'Expense', 'Expense', 'AmountNeutral');
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function writeStyles(XMLWriter $writer): void
    {
        $writer->startElement('Styles');

        $this->writeStyle($writer, 'SheetTitle', [
            'alignment' => ['Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Size' => '16', 'Color' => '#FFFFFF'],
            'interior' => ['Color' => '#0F172A', 'Pattern' => 'Solid'],
            'borders' => $this->fullBorders('#0F172A'),
        ]);

        $this->writeStyle($writer, 'SheetSubtitle', [
            'alignment' => ['Vertical' => 'Center', 'WrapText' => '1'],
            'font' => ['Size' => '10', 'Color' => '#334155'],
            'interior' => ['Color' => '#E2E8F0', 'Pattern' => 'Solid'],
            'borders' => $this->fullBorders('#CBD5E1'),
        ]);

        $this->writeStyle($writer, 'SectionHeader', [
            'alignment' => ['Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Size' => '11', 'Color' => '#0F172A'],
            'interior' => ['Color' => '#DBEAFE', 'Pattern' => 'Solid'],
            'borders' => $this->fullBorders('#BFDBFE'),
        ]);

        $this->writeStyle($writer, 'TableHeader', [
            'alignment' => ['Horizontal' => 'Center', 'Vertical' => 'Center', 'WrapText' => '1'],
            'font' => ['Bold' => '1', 'Color' => '#FFFFFF'],
            'interior' => ['Color' => '#1E293B', 'Pattern' => 'Solid'],
            'borders' => $this->fullBorders('#0F172A'),
        ]);

        $this->writeStyle($writer, 'Label', [
            'alignment' => ['Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#0F172A'],
            'borders' => $this->bottomBorder('#E2E8F0'),
        ]);

        $this->writeStyle($writer, 'Text', [
            'alignment' => ['Vertical' => 'Center'],
            'font' => ['Color' => '#0F172A'],
            'borders' => $this->bottomBorder('#E2E8F0'),
        ]);

        $this->writeStyle($writer, 'TextWrap', [
            'alignment' => ['Vertical' => 'Center', 'WrapText' => '1'],
            'font' => ['Color' => '#0F172A'],
            'borders' => $this->bottomBorder('#E2E8F0'),
        ]);

        $this->writeStyle($writer, 'MutedText', [
            'alignment' => ['Vertical' => 'Center'],
            'font' => ['Color' => '#64748B'],
            'borders' => $this->bottomBorder('#E2E8F0'),
        ]);

        $this->writeStyle($writer, 'Integer', [
            'alignment' => ['Horizontal' => 'Right', 'Vertical' => 'Center'],
            'font' => ['Color' => '#0F172A'],
            'borders' => $this->bottomBorder('#E2E8F0'),
            'number_format' => '0',
        ]);

        $this->writeStyle($writer, 'AmountNeutral', [
            'alignment' => ['Horizontal' => 'Right', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#0F172A'],
            'borders' => $this->bottomBorder('#E2E8F0'),
            'number_format' => '#,##0.00',
        ]);

        $this->writeStyle($writer, 'AmountPositive', [
            'alignment' => ['Horizontal' => 'Right', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#15803D'],
            'borders' => $this->bottomBorder('#E2E8F0'),
            'number_format' => '#,##0.00',
        ]);

        $this->writeStyle($writer, 'AmountNegative', [
            'alignment' => ['Horizontal' => 'Right', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#B91C1C'],
            'borders' => $this->bottomBorder('#E2E8F0'),
            'number_format' => '#,##0.00',
        ]);

        $this->writeStyle($writer, 'Income', [
            'alignment' => ['Horizontal' => 'Right', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#15803D'],
            'borders' => $this->bottomBorder('#E2E8F0'),
            'number_format' => '#,##0.00',
        ]);

        $this->writeStyle($writer, 'Expense', [
            'alignment' => ['Horizontal' => 'Right', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#DC2626'],
            'borders' => $this->bottomBorder('#E2E8F0'),
            'number_format' => '#,##0.00',
        ]);

        $this->writeStyle($writer, 'Percent', [
            'alignment' => ['Horizontal' => 'Right', 'Vertical' => 'Center'],
            'font' => ['Color' => '#0F172A'],
            'borders' => $this->bottomBorder('#E2E8F0'),
            'number_format' => '0.00',
        ]);

        $this->writeStyle($writer, 'BadgeShared', [
            'alignment' => ['Horizontal' => 'Center', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#1D4ED8'],
            'interior' => ['Color' => '#DBEAFE', 'Pattern' => 'Solid'],
            'borders' => $this->fullBorders('#BFDBFE'),
        ]);

        $writer->endElement();
    }

    private function writeWorksheet(
        XMLWriter $writer,
        string $name,
        array $headers,
        array $rows,
        string $title,
        string $subtitle,
        array $columnWidths = []
    ): void {
        $columnCount = count($headers);

        $writer->startElement('Worksheet');
        $writer->writeAttributeNs('ss', 'Name', null, $this->sanitizeWorksheetName($name));

        $writer->startElement('Table');
        $this->writeColumns($writer, $columnWidths);

        $this->writeRow($writer, [
            $this->stringCell($title, 'SheetTitle', max($columnCount - 1, 0)),
        ]);
        $this->writeRow($writer, [
            $this->stringCell($subtitle, 'SheetSubtitle', max($columnCount - 1, 0)),
        ]);
        $this->writeRow($writer, [
            $this->stringCell('', null, max($columnCount - 1, 0)),
        ]);
        $this->writeRow(
            $writer,
            array_map(fn (string $header) => $this->stringCell($header, 'TableHeader'), $headers)
        );

        foreach ($rows as $row) {
            $this->writeRow($writer, $row);
        }

        $writer->endElement();
        $writer->endElement();
    }

    private function writeColumns(XMLWriter $writer, array $columnWidths): void
    {
        foreach ($columnWidths as $width) {
            $writer->startElement('Column');
            $writer->writeAttributeNs('ss', 'AutoFitWidth', null, '0');
            $writer->writeAttributeNs('ss', 'Width', null, (string) $width);
            $writer->endElement();
        }
    }

    private function writeRow(XMLWriter $writer, array $cells): void
    {
        $writer->startElement('Row');

        foreach ($cells as $cell) {
            $this->writeCell(
                $writer,
                $cell['type'] ?? 'String',
                $cell['value'] ?? '',
                $cell['style'] ?? null,
                $cell['merge_across'] ?? null
            );
        }

        $writer->endElement();
    }

    private function writeCell(
        XMLWriter $writer,
        string $type,
        string $value,
        ?string $style = null,
        ?int $mergeAcross = null
    ): void {
        $writer->startElement('Cell');

        if ($style) {
            $writer->writeAttributeNs('ss', 'StyleID', null, $style);
        }

        if ($mergeAcross !== null && $mergeAcross > 0) {
            $writer->writeAttributeNs('ss', 'MergeAcross', null, (string) $mergeAcross);
        }

        $writer->startElement('Data');
        $writer->writeAttributeNs('ss', 'Type', null, $type);
        $writer->text($value);
        $writer->endElement();

        $writer->endElement();
    }

    private function writeStyle(XMLWriter $writer, string $id, array $config): void
    {
        $writer->startElement('Style');
        $writer->writeAttributeNs('ss', 'ID', null, $id);

        if (isset($config['alignment'])) {
            $writer->startElement('Alignment');
            foreach ($config['alignment'] as $attribute => $value) {
                $writer->writeAttributeNs('ss', $attribute, null, (string) $value);
            }
            $writer->endElement();
        }

        if (isset($config['font'])) {
            $writer->startElement('Font');
            foreach ($config['font'] as $attribute => $value) {
                $writer->writeAttributeNs('ss', $attribute, null, (string) $value);
            }
            $writer->endElement();
        }

        if (isset($config['interior'])) {
            $writer->startElement('Interior');
            foreach ($config['interior'] as $attribute => $value) {
                $writer->writeAttributeNs('ss', $attribute, null, (string) $value);
            }
            $writer->endElement();
        }

        if (isset($config['borders'])) {
            $writer->startElement('Borders');
            foreach ($config['borders'] as $border) {
                $writer->startElement('Border');
                foreach ($border as $attribute => $value) {
                    $writer->writeAttributeNs('ss', $attribute, null, (string) $value);
                }
                $writer->endElement();
            }
            $writer->endElement();
        }

        if (isset($config['number_format'])) {
            $writer->startElement('NumberFormat');
            $writer->writeAttributeNs('ss', 'Format', null, $config['number_format']);
            $writer->endElement();
        }

        $writer->endElement();
    }

    private function fullBorders(string $color): array
    {
        return [
            ['Position' => 'Bottom', 'LineStyle' => 'Continuous', 'Weight' => '1', 'Color' => $color],
            ['Position' => 'Left', 'LineStyle' => 'Continuous', 'Weight' => '1', 'Color' => $color],
            ['Position' => 'Right', 'LineStyle' => 'Continuous', 'Weight' => '1', 'Color' => $color],
            ['Position' => 'Top', 'LineStyle' => 'Continuous', 'Weight' => '1', 'Color' => $color],
        ];
    }

    private function bottomBorder(string $color): array
    {
        return [
            ['Position' => 'Bottom', 'LineStyle' => 'Continuous', 'Weight' => '1', 'Color' => $color],
        ];
    }

    private function sectionRow(string $title, int $columnCount): array
    {
        return [
            $this->stringCell($title, 'SectionHeader', max($columnCount - 1, 0)),
        ];
    }

    private function blankRow(int $columnCount): array
    {
        return [
            $this->stringCell('', null, max($columnCount - 1, 0)),
        ];
    }

    private function metricRow(
        string $theme,
        string $metric,
        array $valueCell,
        string $unit = '',
        string $comment = ''
    ): array {
        return [
            $this->stringCell($theme, 'MutedText'),
            $this->stringCell($metric, 'Label'),
            $valueCell,
            $this->stringCell($unit, 'MutedText'),
            $this->stringCell($comment, 'TextWrap'),
        ];
    }

    private function emptyStateRow(string $message, int $columnCount): array
    {
        return [
            $this->stringCell($message, 'MutedText', max($columnCount - 1, 0)),
        ];
    }

    private function stringCell(mixed $value, ?string $style = null, ?int $mergeAcross = null): array
    {
        return [
            'type' => 'String',
            'value' => $value === null ? '' : (string) $value,
            'style' => $style,
            'merge_across' => $mergeAcross,
        ];
    }

    private function numberCell(mixed $value, string $style = 'AmountNeutral'): array
    {
        if ($value === null || $value === '') {
            return $this->stringCell('', 'Text');
        }

        return [
            'type' => 'Number',
            'value' => (string) $value,
            'style' => $style,
            'merge_across' => null,
        ];
    }

    private function amountCell(
        mixed $value,
        string $positiveStyle = 'AmountPositive',
        string $negativeStyle = 'AmountNegative',
        string $zeroStyle = 'AmountNeutral'
    ): array {
        $numericValue = (float) ($value ?? 0);

        if ($numericValue > 0) {
            return $this->numberCell($value, $positiveStyle);
        }

        if ($numericValue < 0) {
            return $this->numberCell($value, $negativeStyle);
        }

        return $this->numberCell($value, $zeroStyle);
    }

    private function formatConversions(array $conversions): string
    {
        if ($conversions === []) {
            return 'None';
        }

        $parts = [];
        foreach ($conversions as $conversion) {
            $parts[] = trim(sprintf(
                '%s %s (%s)',
                $conversion['amount'] ?? 0,
                $conversion['currency'] ?? '',
                $conversion['rate_date'] ?? ''
            ));
        }

        return implode('; ', $parts);
    }

    private function sanitizeWorksheetName(string $name): string
    {
        $name = preg_replace('/[\\\\\\/*?:\\[\\]]/', ' ', $name) ?: 'Sheet';
        $name = trim($name);

        return Str::limit($name !== '' ? $name : 'Sheet', 31, '');
    }
}
