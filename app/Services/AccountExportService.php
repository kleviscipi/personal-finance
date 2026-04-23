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

        $writer->startElementNS(null, 'Workbook', 'urn:schemas-microsoft-com:office:spreadsheet');
        $writer->writeAttributeNs('xmlns', 'o', null, 'urn:schemas-microsoft-com:office:office');
        $writer->writeAttributeNs('xmlns', 'x', null, 'urn:schemas-microsoft-com:office:excel');
        $writer->writeAttributeNs('xmlns', 'ss', null, 'urn:schemas-microsoft-com:office:spreadsheet');
        $writer->writeAttributeNs('xmlns', 'html', null, 'http://www.w3.org/TR/REC-html40');

        $this->writeStyles($writer);

        $this->writeWorksheet(
            $writer,
            'Summary',
            ['Section', 'Metric', 'Value', 'Notes'],
            $this->buildSummaryRows(
                $account,
                $user,
                $referenceMonth,
                $analytics,
                $transactions,
                $budgets,
                $savingsGoals,
                $members
            )
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
            $this->buildMonthlyOverviewRows($analytics)
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
            $this->buildTransactionRows($transactions)
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
            $this->buildBudgetRows($budgets, $analytics['budget_usage'] ?? [])
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
            $this->buildSavingsGoalRows($savingsGoals)
        );

        $this->writeWorksheet(
            $writer,
            'Members',
            ['User ID', 'Name', 'Email', 'Role', 'Active', 'Joined At', 'Invited At'],
            $this->buildMemberRows($members)
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
        $settings = $account->settings;
        $missingRates = $analytics['missing_rates'] ?? [];

        return [
            $this->stringRow('Account', 'Account Name', $account->name, ''),
            $this->stringRow('Account', 'Base Currency', $account->base_currency, ''),
            $this->stringRow('Account', 'Description', $account->description ?: 'None', ''),
            $this->stringRow('Account', 'Selected Month', $referenceMonth->format('F Y'), ''),
            $this->stringRow('Account', 'Exported At', now()->format('Y-m-d H:i:s'), ''),
            $this->stringRow('Account', 'Exported By', $user->email, ''),
            $this->stringRow('Account', 'Locale', $settings?->locale ?: 'Default', ''),
            $this->stringRow('Account', 'Timezone', $settings?->timezone ?: 'Default', ''),
            $this->stringRow(
                'Balances',
                'Total Balance',
                (string) ($analytics['total_balance'] ?? 0),
                $account->base_currency
            ),
            $this->stringRow(
                'Balances',
                'Opening Balance',
                (string) ($analytics['total_balance_opening'] ?? 0),
                $account->base_currency
            ),
            $this->stringRow(
                'Balances',
                'Net Balance',
                (string) ($analytics['total_balance_net'] ?? 0),
                $account->base_currency
            ),
            $this->stringRow(
                'Balances',
                'Balance Conversions',
                $this->formatConversions($analytics['total_balance_conversions'] ?? []),
                'Latest available FX rates'
            ),
            $this->stringRow(
                'Selected Month',
                'Income',
                (string) ($analytics['current_month_income'] ?? 0),
                $account->base_currency
            ),
            $this->stringRow(
                'Selected Month',
                'Expenses',
                (string) ($analytics['current_month_expenses'] ?? 0),
                $account->base_currency
            ),
            $this->stringRow(
                'Selected Month',
                'Net Cash Flow',
                (string) ($analytics['net_cash_flow'] ?? 0),
                $account->base_currency
            ),
            $this->stringRow(
                'Selected Month',
                'Savings',
                (string) ($analytics['current_month_savings']['amount'] ?? 0),
                $account->base_currency
            ),
            $this->stringRow(
                'Selected Month',
                'Savings Rate',
                (string) ($analytics['current_month_savings']['rate'] ?? 0),
                '%'
            ),
            $this->stringRow(
                'Selected Month',
                'Transaction Count',
                (string) ($analytics['current_month_transaction_count'] ?? 0),
                ''
            ),
            $this->stringRow(
                'Selected Month',
                'Missing FX Transactions',
                (string) ($missingRates['count'] ?? 0),
                ! empty($missingRates['currencies']) ? implode(', ', $missingRates['currencies']) : ''
            ),
            $this->stringRow('Data Volume', 'Transactions Exported', (string) $transactions->count(), 'Full account history'),
            $this->stringRow('Data Volume', 'Budgets Exported', (string) $budgets->count(), 'Visible to current user'),
            $this->stringRow('Data Volume', 'Savings Goals Exported', (string) $savingsGoals->count(), 'Visible to current user'),
            $this->stringRow('Data Volume', 'Members Exported', (string) $members->count(), ''),
        ];
    }

    private function buildMonthlyOverviewRows(array $analytics): array
    {
        $balanceHistory = collect($analytics['balance_history'] ?? [])->keyBy('month');

        return collect($analytics['monthly_summary'] ?? [])
            ->map(function (array $row) use ($balanceHistory) {
                $balanceRow = $balanceHistory->get($row['month'], []);

                return [
                    $this->stringCell($row['month'] ?? ''),
                    $this->numberCell($row['income'] ?? 0),
                    $this->numberCell($row['expenses'] ?? 0),
                    $this->numberCell($row['net'] ?? 0),
                    $this->numberCell($balanceRow['balance'] ?? 0),
                    $this->numberCell($balanceRow['savings'] ?? 0),
                ];
            })
            ->all();
    }

    private function buildTransactionRows(Collection $transactions): array
    {
        return $transactions
            ->map(function (Transaction $transaction) {
                return [
                    $this->numberCell($transaction->id),
                    $this->stringCell($this->formatDate($transaction->date)),
                    $this->stringCell(ucfirst($transaction->type)),
                    $this->numberCell($transaction->amount),
                    $this->stringCell($transaction->currency),
                    $this->stringCell($transaction->category?->name ?: ''),
                    $this->stringCell($transaction->subcategory?->name ?: ''),
                    $this->stringCell($transaction->description ?: ''),
                    $this->stringCell($transaction->payment_method ?: ''),
                    $this->stringCell($transaction->creator?->name ?: $transaction->creator?->email ?: ''),
                    $this->stringCell($transaction->tags->pluck('name')->implode(', ')),
                    $this->stringCell($this->formatYesNo((bool) data_get($transaction->metadata, 'opening_balance', false))),
                    $this->stringCell($this->formatDateTime($transaction->created_at)),
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
                    $this->numberCell($budget->id),
                    $this->stringCell($budget->category?->name ?: 'All categories'),
                    $this->stringCell($budget->subcategory?->name ?: ''),
                    $this->stringCell($budget->user_id ? 'Personal' : 'Account-wide'),
                    $this->stringCell($budget->user?->name ?: $budget->user?->email ?: ''),
                    $this->numberCell($budget->amount),
                    $this->stringCell($budget->currency),
                    $this->stringCell(ucfirst($budget->period)),
                    $this->stringCell($this->formatDate($budget->start_date)),
                    $this->stringCell($this->formatDate($budget->end_date)),
                    $this->stringCell($this->formatYesNo($usage !== null)),
                    $this->numberCell($budgetAmount),
                    $this->numberCell($spentAmount),
                    $this->numberCell($remainingAmount),
                    $this->numberCell($this->calculatePercentage($spentAmount, $budgetAmount)),
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
                    $this->numberCell($goal->id),
                    $this->stringCell($goal->name),
                    $this->stringCell($goal->user_id ? 'Personal' : 'Account-wide'),
                    $this->stringCell($goal->user?->name ?: $goal->user?->email ?: ''),
                    $this->numberCell($goal->target_amount),
                    $this->stringCell($goal->currency),
                    $this->numberCell($goal->initial_amount),
                    $this->numberCell($progress['current_amount'] ?? 0),
                    $this->numberCell($progress['contributed'] ?? 0),
                    $this->numberCell($progress['remaining'] ?? 0),
                    $this->numberCell($progress['percentage'] ?? 0),
                    $this->stringCell($this->formatYesNo((bool) ($progress['is_complete'] ?? false))),
                    $this->stringCell($this->formatTrackingMode($goal->tracking_mode)),
                    $this->stringCell($goal->category?->name ?: ''),
                    $this->stringCell($goal->subcategory?->name ?: ''),
                    $this->stringCell($this->formatDate($goal->start_date)),
                    $this->stringCell($this->formatDate($goal->target_date)),
                    $this->stringCell($projection['projected_completion_date'] ?? ''),
                    $this->numberCell($projection['required_monthly'] ?? null),
                ];
            })
            ->all();
    }

    private function buildMemberRows(Collection $members): array
    {
        return $members
            ->map(function (User $member) {
                return [
                    $this->numberCell($member->id),
                    $this->stringCell($member->name),
                    $this->stringCell($member->email),
                    $this->stringCell((string) $member->pivot?->role),
                    $this->stringCell($this->formatYesNo((bool) $member->pivot?->is_active)),
                    $this->stringCell($this->formatDateTime($member->pivot?->joined_at)),
                    $this->stringCell($this->formatDateTime($member->pivot?->invited_at)),
                ];
            })
            ->all();
    }

    private function writeStyles(XMLWriter $writer): void
    {
        $writer->startElement('Styles');

        $writer->startElement('Style');
        $writer->writeAttributeNs('ss', 'ID', null, 'Header');
        $writer->startElement('Font');
        $writer->writeAttributeNs('ss', 'Bold', null, '1');
        $writer->endElement();
        $writer->startElement('Interior');
        $writer->writeAttributeNs('ss', 'Color', null, '#E2E8F0');
        $writer->writeAttributeNs('ss', 'Pattern', null, 'Solid');
        $writer->endElement();
        $writer->endElement();

        $writer->endElement();
    }

    private function writeWorksheet(XMLWriter $writer, string $name, array $headers, array $rows): void
    {
        $writer->startElement('Worksheet');
        $writer->writeAttributeNs('ss', 'Name', null, $this->sanitizeWorksheetName($name));

        $writer->startElement('Table');
        $this->writeRow(
            $writer,
            array_map(fn (string $header) => $this->stringCell($header, 'Header'), $headers)
        );

        foreach ($rows as $row) {
            $this->writeRow($writer, $row);
        }

        $writer->endElement();
        $writer->endElement();
    }

    private function writeRow(XMLWriter $writer, array $cells): void
    {
        $writer->startElement('Row');

        foreach ($cells as $cell) {
            $this->writeCell(
                $writer,
                $cell['type'] ?? 'String',
                $cell['value'] ?? '',
                $cell['style'] ?? null
            );
        }

        $writer->endElement();
    }

    private function writeCell(XMLWriter $writer, string $type, string $value, ?string $style = null): void
    {
        $writer->startElement('Cell');

        if ($style) {
            $writer->writeAttributeNs('ss', 'StyleID', null, $style);
        }

        $writer->startElement('Data');
        $writer->writeAttributeNs('ss', 'Type', null, $type);
        $writer->text($value);
        $writer->endElement();

        $writer->endElement();
    }

    private function stringRow(string $section, string $metric, string $value, string $notes): array
    {
        return [
            $this->stringCell($section),
            $this->stringCell($metric),
            $this->stringCell($value),
            $this->stringCell($notes),
        ];
    }

    private function stringCell(mixed $value, ?string $style = null): array
    {
        return [
            'type' => 'String',
            'value' => $value === null ? '' : (string) $value,
            'style' => $style,
        ];
    }

    private function numberCell(mixed $value): array
    {
        if ($value === null || $value === '') {
            return $this->stringCell('');
        }

        return [
            'type' => 'Number',
            'value' => (string) $value,
            'style' => null,
        ];
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
