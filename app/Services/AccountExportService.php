<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Budget;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use XMLWriter;

class AccountExportService
{
    private const SPREADSHEET_NS = 'urn:schemas-microsoft-com:office:spreadsheet';

    private const EXCEL_NS = 'urn:schemas-microsoft-com:office:excel';

    public function __construct(
        private AnalyticsService $analyticsService,
        private SavingsGoalService $savingsGoalService
    ) {}

    public function buildWorkbook(Account $account, User $user, Carbon $referenceMonth): string
    {
        $account->loadMissing('settings');

        $analytics = $this->analyticsService->getDashboardData($account, $user, $referenceMonth);
        $transactions = $this->loadTransactions($account);
        $budgets = $this->loadVisibleBudgets($account, $user);
        $savingsGoals = $this->loadVisibleSavingsGoals($account, $user);
        $members = $account->users()
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('users.name')
            ->get();

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

        $this->writeWorksheet(
            $writer,
            'Summary',
            ['Theme', 'Metric', 'Value', 'Unit', 'Comment'],
            $this->buildSummaryRows(
                $account,
                $user,
                $referenceMonth,
                $analytics,
                $transactions,
                $budgets,
                $savingsGoals,
                $members
            ),
            sprintf('%s Finance Snapshot', $account->name),
            sprintf(
                'Month in focus: %s | Base currency: %s | Exported %s',
                $referenceMonth->format('F Y'),
                $account->base_currency,
                now()->format('Y-m-d H:i')
            ),
            [100, 180, 110, 80, 300]
        );

        $this->writeWorksheet(
            $writer,
            'Insights',
            ['Lens', 'Insight', 'Amount', 'Unit', 'Comment'],
            $this->buildInsightsRows($account, $analytics),
            'Spending Insights',
            'Top categories, subcategories, and unusual spending spikes.',
            [100, 180, 110, 80, 300]
        );

        $this->writeWorksheet(
            $writer,
            'Monthly Overview',
            [
                'Month',
                sprintf('Income (%s)', $account->base_currency),
                sprintf('Expenses (%s)', $account->base_currency),
                sprintf('Net (%s)', $account->base_currency),
                sprintf('Ending Balance (%s)', $account->base_currency),
                sprintf('Monthly Savings (%s)', $account->base_currency),
            ],
            $this->buildMonthlyOverviewRows($analytics),
            'Monthly Overview',
            'Twelve-month cash flow and balance trend.',
            [85, 110, 110, 110, 120, 120]
        );

        $this->writeWorksheet(
            $writer,
            'Transactions',
            [
                'Transaction ID',
                'Date',
                'Type',
                'Amount',
                'Currency',
                'Category',
                'Subcategory',
                'Description',
                'Payment Method',
                'Created By',
                'Tags',
                'Opening Balance',
                'Created At',
            ],
            $this->buildTransactionRows($transactions),
            'Transaction Ledger',
            'Complete account history with categories, tags, and creator details.',
            [60, 85, 85, 90, 65, 120, 120, 220, 120, 120, 150, 90, 120]
        );

        $this->writeWorksheet(
            $writer,
            'Budgets',
            [
                'Budget ID',
                'Category',
                'Subcategory',
                'Scope',
                'Owner',
                'Original Budget Amount',
                'Original Currency',
                'Period',
                'Start Date',
                'End Date',
                'Active In Selected Month',
                sprintf('Selected Month Budget (%s)', $account->base_currency),
                sprintf('Selected Month Spent (%s)', $account->base_currency),
                sprintf('Selected Month Remaining (%s)', $account->base_currency),
                'Selected Month Usage %',
            ],
            $this->buildBudgetRows($budgets, $analytics['budget_usage'] ?? []),
            'Budget Tracker',
            sprintf('Budget health for %s.', $referenceMonth->format('F Y')),
            [60, 130, 130, 90, 120, 110, 80, 80, 85, 85, 100, 110, 110, 110, 90]
        );

        $this->writeWorksheet(
            $writer,
            'Savings Goals',
            [
                'Goal ID',
                'Name',
                'Scope',
                'Owner',
                'Target Amount',
                'Currency',
                'Initial Amount',
                'Current Amount',
                'Contributed',
                'Remaining',
                'Progress %',
                'Complete',
                'Tracking Mode',
                'Category',
                'Subcategory',
                'Start Date',
                'Target Date',
                'Projected Completion',
                'Required Monthly',
            ],
            $this->buildSavingsGoalRows($savingsGoals),
            'Savings Goals',
            'Progress, remaining target, and completion outlook.',
            [60, 150, 90, 120, 100, 75, 100, 100, 100, 100, 90, 80, 110, 120, 120, 85, 85, 110, 100]
        );

        $this->writeWorksheet(
            $writer,
            'Members',
            ['User ID', 'Name', 'Email', 'Role', 'Active', 'Joined At', 'Invited At'],
            $this->buildMemberRows($members),
            'Account Members',
            'Who has access to this account and when they joined.',
            [60, 140, 200, 90, 80, 120, 120]
        );

        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    public function buildFileName(Account $account, Carbon $referenceMonth): string
    {
        $slug = trim((string) Str::of($account->name)->slug('-'));

        return sprintf(
            '%s-%s-overview.xls',
            $slug !== '' ? $slug : 'account',
            $referenceMonth->format('Y-m')
        );
    }

    private function loadTransactions(Account $account): Collection
    {
        return Transaction::with(['category', 'subcategory', 'creator', 'tags'])
            ->where('account_id', $account->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
    }

    private function loadVisibleBudgets(Account $account, User $user): Collection
    {
        return Budget::with(['category', 'subcategory', 'user'])
            ->where('account_id', $account->id)
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->latest('start_date')
            ->get();
    }

    private function loadVisibleSavingsGoals(Account $account, User $user): Collection
    {
        return SavingsGoal::with(['category', 'subcategory', 'user'])
            ->where('account_id', $account->id)
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->latest('target_date')
            ->get();
    }

    private function buildSummaryRows(
        Account $account,
        User $user,
        Carbon $referenceMonth,
        array $analytics,
        Collection $transactions,
        Collection $budgets,
        Collection $savingsGoals,
        Collection $members
    ): array {
        $missingRates = $analytics['missing_rates'] ?? [];
        $settings = $account->settings;
        $forecast = $analytics['forecast'] ?? [];
        $latestTransactionDate = $transactions->first()?->date;
        $rows = [];

        $rows[] = $this->sectionRow('Account Snapshot', 5);
        $rows[] = $this->metricRow(
            'Account',
            'Account Name',
            $this->stringCell($account->name, 'Text'),
            '',
            'Primary workspace for this export.'
        );
        $rows[] = $this->metricRow(
            'Account',
            'Base Currency',
            $this->stringCell($account->base_currency, 'BadgeShared'),
            '',
            'All dashboard analytics are normalized to this currency.'
        );
        $rows[] = $this->metricRow(
            'Account',
            'Description',
            $this->stringCell($account->description ?: 'No description', 'TextWrap'),
            '',
            'Useful when the account is shared across a family or team.'
        );
        $rows[] = $this->metricRow(
            'Account',
            'Selected Month',
            $this->stringCell($referenceMonth->format('F Y'), 'Text'),
            '',
            'The same month filter used on the dashboard.'
        );
        $rows[] = $this->metricRow(
            'Account',
            'Exported By',
            $this->stringCell($user->name ?: $user->email, 'Text'),
            '',
            $user->email
        );
        $rows[] = $this->metricRow(
            'Account',
            'Locale / Timezone',
            $this->stringCell(($settings?->locale ?: 'Default').' / '.($settings?->timezone ?: 'Default'), 'Text'),
            '',
            'Account presentation settings.'
        );

        $rows[] = $this->blankRow(5);
        $rows[] = $this->sectionRow('Cash Position', 5);
        $rows[] = $this->metricRow(
            'Balance',
            'Total Balance',
            $this->amountCell($analytics['total_balance'] ?? 0),
            $account->base_currency,
            'All recorded income minus all recorded expenses.'
        );
        $rows[] = $this->metricRow(
            'Balance',
            'Opening Balance',
            $this->amountCell($analytics['total_balance_opening'] ?? 0),
            $account->base_currency,
            'Imported or opening-balance transactions only.'
        );
        $rows[] = $this->metricRow(
            'Balance',
            'Net Balance',
            $this->amountCell($analytics['total_balance_net'] ?? 0),
            $account->base_currency,
            'Balance excluding opening-balance adjustments.'
        );
        $rows[] = $this->metricRow(
            'Balance',
            'Balance Conversions',
            $this->stringCell($this->formatConversions($analytics['total_balance_conversions'] ?? []), 'TextWrap'),
            '',
            'Latest available FX rates.'
        );

        $rows[] = $this->blankRow(5);
        $rows[] = $this->sectionRow('Month in Focus', 5);
        $rows[] = $this->metricRow(
            'Month',
            'Income',
            $this->numberCell($analytics['current_month_income'] ?? 0, 'Income'),
            $account->base_currency,
            'Posted income in the selected month.'
        );
        $rows[] = $this->metricRow(
            'Month',
            'Expenses',
            $this->numberCell($analytics['current_month_expenses'] ?? 0, 'Expense'),
            $account->base_currency,
            'Posted expenses in the selected month.'
        );
        $rows[] = $this->metricRow(
            'Month',
            'Net Cash Flow',
            $this->amountCell($analytics['net_cash_flow'] ?? 0),
            $account->base_currency,
            'Income minus expenses for the selected month.'
        );
        $rows[] = $this->metricRow(
            'Month',
            'Savings',
            $this->amountCell($analytics['current_month_savings']['amount'] ?? 0, 'Savings', 'Expense', 'AmountNeutral'),
            $account->base_currency,
            'Current month savings or shortfall.'
        );
        $rows[] = $this->metricRow(
            'Month',
            'Savings Rate',
            $this->numberCell($analytics['current_month_savings']['rate'] ?? 0, 'PercentStrong'),
            '%',
            'Share of income kept after expenses.'
        );
        $rows[] = $this->metricRow(
            'Month',
            'Transaction Count',
            $this->numberCell($analytics['current_month_transaction_count'] ?? 0, 'Integer'),
            '',
            'Posted transactions excluding opening balance rows.'
        );

        $rows[] = $this->blankRow(5);
        $rows[] = $this->sectionRow('Forward Look', 5);
        $rows[] = $this->metricRow(
            'Forecast',
            '30 Day Net Forecast',
            $this->amountCell(data_get($forecast, 'forecast_30.net', 0)),
            $account->base_currency,
            'Based on the trailing 30-day daily average.'
        );
        $rows[] = $this->metricRow(
            'Forecast',
            '90 Day Net Forecast',
            $this->amountCell(data_get($forecast, 'forecast_90.net', 0)),
            $account->base_currency,
            'Extended projection from the same recent trend.'
        );
        $rows[] = $this->metricRow(
            'Forecast',
            'Tracked Budget Lines',
            $this->numberCell(count($analytics['budget_usage'] ?? []), 'Integer'),
            '',
            'Visible budget lines active in the selected month.'
        );
        $rows[] = $this->metricRow(
            'Forecast',
            'Tracked Savings Goals',
            $this->numberCell($savingsGoals->count(), 'Integer'),
            '',
            'Goals visible to the exporting user.'
        );

        $rows[] = $this->blankRow(5);
        $rows[] = $this->sectionRow('Data Coverage', 5);
        $rows[] = $this->metricRow(
            'Coverage',
            'Transactions Exported',
            $this->numberCell($transactions->count(), 'Integer'),
            '',
            'Complete ledger, not only the selected month.'
        );
        $rows[] = $this->metricRow(
            'Coverage',
            'Budgets Exported',
            $this->numberCell($budgets->count(), 'Integer'),
            '',
            'Budget rows visible to the exporting user.'
        );
        $rows[] = $this->metricRow(
            'Coverage',
            'Members Included',
            $this->numberCell($members->count(), 'Integer'),
            '',
            'Current account members with role metadata.'
        );
        $rows[] = $this->metricRow(
            'Coverage',
            'Latest Transaction Date',
            $this->stringCell($this->formatDate($latestTransactionDate), 'Text'),
            '',
            'Most recent posted transaction in the account.'
        );
        $rows[] = $this->metricRow(
            'Coverage',
            'Missing FX Transactions',
            $this->numberCell($missingRates['count'] ?? 0, 'Integer'),
            '',
            ! empty($missingRates['currencies'])
                ? 'Currencies affected: '.implode(', ', $missingRates['currencies'])
                : 'No FX gaps detected in the selected month.'
        );

        return $rows;
    }

    private function buildInsightsRows(Account $account, array $analytics): array
    {
        $rows = [];

        $rows[] = $this->sectionRow('Top Categories (Last 30 Days)', 5);
        foreach ($analytics['top_categories'] ?? [] as $category) {
            $rows[] = $this->metricRow(
                'Category',
                $category['category'] ?? 'Unknown',
                $this->amountCell($category['total'] ?? 0),
                $account->base_currency,
                sprintf('Share of spend: %s%%', $category['percentage'] ?? 0)
            );
        }

        if (empty($analytics['top_categories'])) {
            $rows[] = $this->metricRow(
                'Category',
                'No category spend yet',
                $this->stringCell('No data', 'MutedText'),
                '',
                'Expense insights appear once the account has expense history.'
            );
        }

        $rows[] = $this->blankRow(5);
        $rows[] = $this->sectionRow('Top Subcategories (Last 30 Days)', 5);
        foreach ($analytics['top_subcategories'] ?? [] as $subcategory) {
            $rows[] = $this->metricRow(
                'Subcategory',
                $subcategory['label'] ?? $subcategory['subcategory'] ?? 'Unknown',
                $this->amountCell($subcategory['total'] ?? 0),
                $account->base_currency,
                sprintf('Share of spend: %s%%', $subcategory['percentage'] ?? 0)
            );
        }

        if (empty($analytics['top_subcategories'])) {
            $rows[] = $this->metricRow(
                'Subcategory',
                'No subcategory spend yet',
                $this->stringCell('No data', 'MutedText'),
                '',
                'Create subcategories to deepen expense analysis.'
            );
        }

        $rows[] = $this->blankRow(5);
        $rows[] = $this->sectionRow('Category Spikes', 5);
        foreach ($analytics['category_spikes'] ?? [] as $spike) {
            $rows[] = $this->metricRow(
                'Spike',
                $spike['category'] ?? 'Unknown',
                $this->amountCell($spike['recent_total'] ?? 0),
                $account->base_currency,
                sprintf(
                    '+%s%% vs baseline %s %s',
                    $spike['delta_percent'] ?? 0,
                    $spike['baseline'] ?? 0,
                    $account->base_currency
                )
            );
        }

        if (empty($analytics['category_spikes'])) {
            $rows[] = $this->metricRow(
                'Spike',
                'No unusual category spikes',
                $this->stringCell('Stable', 'BadgePositive'),
                '',
                'No category exceeded the configured spike threshold.'
            );
        }

        return $rows;
    }

    private function buildMonthlyOverviewRows(array $analytics): array
    {
        $balanceHistory = collect($analytics['balance_history'] ?? [])->keyBy('month');

        return collect($analytics['monthly_summary'] ?? [])
            ->map(function (array $row) use ($balanceHistory) {
                $balanceRow = $balanceHistory->get($row['month'], []);

                return [
                    $this->stringCell($row['month'] ?? '', 'Text'),
                    $this->numberCell($row['income'] ?? 0, 'Income'),
                    $this->numberCell($row['expenses'] ?? 0, 'Expense'),
                    $this->amountCell($row['net'] ?? 0),
                    $this->amountCell($balanceRow['balance'] ?? 0),
                    $this->amountCell($balanceRow['savings'] ?? 0, 'Savings', 'Expense', 'AmountNeutral'),
                ];
            })
            ->all();
    }

    private function buildTransactionRows(Collection $transactions): array
    {
        return $transactions
            ->map(function (Transaction $transaction) {
                $amountStyle = match ($transaction->type) {
                    'income' => 'Income',
                    'expense' => 'Expense',
                    default => 'AmountNeutral',
                };

                $typeStyle = match ($transaction->type) {
                    'income' => 'TypeIncome',
                    'expense' => 'TypeExpense',
                    default => 'TypeTransfer',
                };

                return [
                    $this->numberCell($transaction->id, 'Integer'),
                    $this->stringCell($this->formatDate($transaction->date), 'Text'),
                    $this->stringCell(ucfirst($transaction->type), $typeStyle),
                    $this->numberCell($transaction->amount, $amountStyle),
                    $this->stringCell($transaction->currency, 'MutedText'),
                    $this->stringCell($transaction->category?->name ?: '', 'Text'),
                    $this->stringCell($transaction->subcategory?->name ?: '', 'Text'),
                    $this->stringCell($transaction->description ?: '', 'TextWrap'),
                    $this->stringCell($transaction->payment_method ?: '', 'Text'),
                    $this->stringCell($transaction->creator?->name ?: $transaction->creator?->email ?: '', 'Text'),
                    $this->stringCell($transaction->tags->pluck('name')->implode(', '), 'TextWrap'),
                    $this->yesNoCell((bool) data_get($transaction->metadata, 'opening_balance', false)),
                    $this->stringCell($this->formatDateTime($transaction->created_at), 'MutedText'),
                ];
            })
            ->all();
    }

    private function buildBudgetRows(Collection $budgets, array $budgetUsage): array
    {
        $usageByBudgetId = collect($budgetUsage)->keyBy('id');

        return $budgets
            ->map(function (Budget $budget) use ($usageByBudgetId) {
                $usage = $usageByBudgetId->get($budget->id);
                $budgetAmount = data_get($usage, 'budget');
                $spentAmount = data_get($usage, 'spent');
                $remainingAmount = data_get($usage, 'remaining');

                return [
                    $this->numberCell($budget->id, 'Integer'),
                    $this->stringCell($budget->category?->name ?: 'All categories', 'Text'),
                    $this->stringCell($budget->subcategory?->name ?: '', 'Text'),
                    $this->scopeCell($budget->user_id !== null),
                    $this->stringCell($budget->user?->name ?: $budget->user?->email ?: '', 'Text'),
                    $this->numberCell($budget->amount, 'AmountNeutral'),
                    $this->stringCell($budget->currency, 'MutedText'),
                    $this->stringCell(ucfirst($budget->period), 'Text'),
                    $this->stringCell($this->formatDate($budget->start_date), 'Text'),
                    $this->stringCell($this->formatDate($budget->end_date), 'Text'),
                    $this->yesNoCell($usage !== null),
                    $this->numberCell($budgetAmount, 'AmountNeutral'),
                    $this->numberCell($spentAmount, 'Expense'),
                    $this->amountCell($remainingAmount),
                    $this->numberCell($this->calculatePercentage($spentAmount, $budgetAmount), 'Percent'),
                ];
            })
            ->all();
    }

    private function buildSavingsGoalRows(Collection $savingsGoals): array
    {
        return $savingsGoals
            ->map(function (SavingsGoal $goal) {
                $progress = $this->savingsGoalService->calculateProgress($goal);
                $projection = $this->savingsGoalService->calculateProjection($goal);

                return [
                    $this->numberCell($goal->id, 'Integer'),
                    $this->stringCell($goal->name, 'Text'),
                    $this->scopeCell($goal->user_id !== null),
                    $this->stringCell($goal->user?->name ?: $goal->user?->email ?: '', 'Text'),
                    $this->numberCell($goal->target_amount, 'AmountNeutral'),
                    $this->stringCell($goal->currency, 'MutedText'),
                    $this->numberCell($goal->initial_amount, 'AmountNeutral'),
                    $this->amountCell($progress['current_amount'] ?? 0, 'Savings', 'Expense', 'AmountNeutral'),
                    $this->numberCell($progress['contributed'] ?? 0, 'Savings'),
                    $this->amountCell($progress['remaining'] ?? 0),
                    $this->numberCell($progress['percentage'] ?? 0, 'Percent'),
                    $this->yesNoCell((bool) ($progress['is_complete'] ?? false)),
                    $this->stringCell($this->formatTrackingMode($goal->tracking_mode), 'Text'),
                    $this->stringCell($goal->category?->name ?: '', 'Text'),
                    $this->stringCell($goal->subcategory?->name ?: '', 'Text'),
                    $this->stringCell($this->formatDate($goal->start_date), 'Text'),
                    $this->stringCell($this->formatDate($goal->target_date), 'Text'),
                    $this->stringCell($projection['projected_completion_date'] ?? '', 'Text'),
                    $this->numberCell($projection['required_monthly'] ?? null, 'AmountNeutral'),
                ];
            })
            ->all();
    }

    private function buildMemberRows(Collection $members): array
    {
        return $members
            ->map(function (User $member) {
                return [
                    $this->numberCell($member->id, 'Integer'),
                    $this->stringCell($member->name, 'Text'),
                    $this->stringCell($member->email, 'Text'),
                    $this->stringCell(Str::headline((string) $member->pivot?->role), 'Text'),
                    $this->yesNoCell((bool) $member->pivot?->is_active),
                    $this->stringCell($this->formatDateTime($member->pivot?->joined_at), 'Text'),
                    $this->stringCell($this->formatDateTime($member->pivot?->invited_at), 'Text'),
                ];
            })
            ->all();
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

        $this->writeStyle($writer, 'Savings', [
            'alignment' => ['Horizontal' => 'Right', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#2563EB'],
            'borders' => $this->bottomBorder('#E2E8F0'),
            'number_format' => '#,##0.00',
        ]);

        $this->writeStyle($writer, 'Percent', [
            'alignment' => ['Horizontal' => 'Right', 'Vertical' => 'Center'],
            'font' => ['Color' => '#0F172A'],
            'borders' => $this->bottomBorder('#E2E8F0'),
            'number_format' => '0.00',
        ]);

        $this->writeStyle($writer, 'PercentStrong', [
            'alignment' => ['Horizontal' => 'Right', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#0F172A'],
            'borders' => $this->bottomBorder('#E2E8F0'),
            'number_format' => '0.00',
        ]);

        $this->writeStyle($writer, 'BadgeShared', [
            'alignment' => ['Horizontal' => 'Center', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#1D4ED8'],
            'interior' => ['Color' => '#DBEAFE', 'Pattern' => 'Solid'],
            'borders' => $this->fullBorders('#BFDBFE'),
        ]);

        $this->writeStyle($writer, 'BadgePersonal', [
            'alignment' => ['Horizontal' => 'Center', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#7C3AED'],
            'interior' => ['Color' => '#EDE9FE', 'Pattern' => 'Solid'],
            'borders' => $this->fullBorders('#DDD6FE'),
        ]);

        $this->writeStyle($writer, 'BadgePositive', [
            'alignment' => ['Horizontal' => 'Center', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#166534'],
            'interior' => ['Color' => '#DCFCE7', 'Pattern' => 'Solid'],
            'borders' => $this->fullBorders('#BBF7D0'),
        ]);

        $this->writeStyle($writer, 'BadgeMuted', [
            'alignment' => ['Horizontal' => 'Center', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#475569'],
            'interior' => ['Color' => '#E2E8F0', 'Pattern' => 'Solid'],
            'borders' => $this->fullBorders('#CBD5E1'),
        ]);

        $this->writeStyle($writer, 'TypeIncome', [
            'alignment' => ['Horizontal' => 'Center', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#FFFFFF'],
            'interior' => ['Color' => '#16A34A', 'Pattern' => 'Solid'],
            'borders' => $this->fullBorders('#15803D'),
        ]);

        $this->writeStyle($writer, 'TypeExpense', [
            'alignment' => ['Horizontal' => 'Center', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#FFFFFF'],
            'interior' => ['Color' => '#DC2626', 'Pattern' => 'Solid'],
            'borders' => $this->fullBorders('#B91C1C'),
        ]);

        $this->writeStyle($writer, 'TypeTransfer', [
            'alignment' => ['Horizontal' => 'Center', 'Vertical' => 'Center'],
            'font' => ['Bold' => '1', 'Color' => '#FFFFFF'],
            'interior' => ['Color' => '#475569', 'Pattern' => 'Solid'],
            'borders' => $this->fullBorders('#334155'),
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

    private function yesNoCell(bool $value): array
    {
        return $this->stringCell($value ? 'Yes' : 'No', $value ? 'BadgePositive' : 'BadgeMuted');
    }

    private function scopeCell(bool $personal): array
    {
        return $this->stringCell($personal ? 'Personal' : 'Account-wide', $personal ? 'BadgePersonal' : 'BadgeShared');
    }

    private function calculatePercentage(mixed $spent, mixed $budget): string
    {
        $budgetValue = (float) ($budget ?? 0);

        if ($budgetValue <= 0) {
            return '0';
        }

        return (string) round((((float) ($spent ?? 0)) / $budgetValue) * 100, 2);
    }

    private function formatConversions(array $conversions): string
    {
        if ($conversions === []) {
            return 'None';
        }

        return collect($conversions)
            ->map(function (array $conversion) {
                $amount = $conversion['amount'] ?? 0;
                $currency = $conversion['currency'] ?? '';
                $rateDate = $conversion['rate_date'] ?? '';

                return trim(sprintf('%s %s (%s)', $amount, $currency, $rateDate));
            })
            ->implode('; ');
    }

    private function formatTrackingMode(?string $trackingMode): string
    {
        return $trackingMode
            ? Str::headline(str_replace('_', ' ', $trackingMode))
            : '';
    }

    private function formatYesNo(bool $value): string
    {
        return $value ? 'Yes' : 'No';
    }

    private function formatDate(mixed $value): string
    {
        if (! $value) {
            return '';
        }

        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        return Carbon::parse($value)->toDateString();
    }

    private function formatDateTime(mixed $value): string
    {
        if (! $value) {
            return '';
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i:s');
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    private function sanitizeWorksheetName(string $name): string
    {
        $name = preg_replace('/[\\\\\\/*?:\\[\\]]/', ' ', $name) ?: 'Sheet';
        $name = trim($name);

        return Str::limit($name !== '' ? $name : 'Sheet', 31, '');
    }
}
