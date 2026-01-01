<?php

namespace App\Services;

use App\Models\SavingsGoal;
use App\Support\DecimalMath;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SavingsGoalService
{
    private const DECIMAL_PRECISION = 4;
    private const PERCENTAGE_PRECISION = 2;

    public function calculateProgress(SavingsGoal $goal, ?Carbon $date = null): array
    {
        $date = $date ?? now();
        $startDate = $goal->start_date ?? $goal->created_at?->toDateString() ?? $date->toDateString();

        $contributed = $this->sumContributions(
            $goal,
            Carbon::parse($startDate)->startOfDay(),
            $date->endOfDay()
        );

        $currentAmount = DecimalMath::add($goal->initial_amount, $contributed, self::DECIMAL_PRECISION);
        $remaining = DecimalMath::sub($goal->target_amount, $currentAmount, self::DECIMAL_PRECISION);
        $percentage = $goal->target_amount > 0
            ? DecimalMath::mul(
                DecimalMath::div($currentAmount, $goal->target_amount, self::DECIMAL_PRECISION),
                100,
                self::PERCENTAGE_PRECISION
            )
            : 0;

        return [
            'current_amount' => $currentAmount,
            'contributed' => $contributed,
            'remaining' => $remaining,
            'percentage' => $percentage,
            'is_complete' => DecimalMath::comp($currentAmount, $goal->target_amount, self::DECIMAL_PRECISION) >= 0,
        ];
    }

    public function estimateMonthlyContribution(SavingsGoal $goal, int $months = 3): string
    {
        $months = max(1, $months);
        $end = now()->endOfDay();
        $start = now()->subMonths($months)->startOfDay();

        $total = $this->sumContributions($goal, $start, $end);
        return DecimalMath::div($total, (string) $months, self::DECIMAL_PRECISION);
    }

    public function calculateProjection(
        SavingsGoal $goal,
        ?string $monthlyContribution = null,
        int $monthsForAverage = 3
    ): array {
        $progress = $this->calculateProgress($goal);
        $remaining = $progress['remaining'];

        $averageMonthly = $this->estimateMonthlyContribution($goal, $monthsForAverage);
        $monthly = $monthlyContribution ?? $averageMonthly;

        $projectedDate = $this->projectCompletionDate($remaining, $monthly);
        $requiredMonthly = $this->requiredMonthlyToTargetDate($remaining, $goal->target_date);

        return [
            'average_monthly' => $averageMonthly,
            'monthly_used' => $monthly,
            'projected_completion_date' => $projectedDate?->toDateString(),
            'required_monthly' => $requiredMonthly,
        ];
    }

    private function sumContributions(SavingsGoal $goal, Carbon $start, Carbon $end): string
    {
        if ($goal->tracking_mode === 'manual') {
            return '0';
        }

        if ($goal->tracking_mode === 'net_savings') {
            $incomeQuery = DB::table('transactions')
                ->where('account_id', $goal->account_id)
                ->where('type', 'income')
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->whereNull('deleted_at');

            $expenseQuery = DB::table('transactions')
                ->where('account_id', $goal->account_id)
                ->where('type', 'expense')
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->whereNull('deleted_at');

            if ($goal->user_id) {
                $incomeQuery->where('created_by', $goal->user_id);
                $expenseQuery->where('created_by', $goal->user_id);
            }

            $income = $incomeQuery->sum('amount');
            $expenses = $expenseQuery->sum('amount');

            return DecimalMath::sub($income, $expenses, self::DECIMAL_PRECISION);
        }

        $query = DB::table('transactions')
            ->where('account_id', $goal->account_id)
            ->where('type', 'expense')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNull('deleted_at');

        if ($goal->user_id) {
            $query->where('created_by', $goal->user_id);
        }

        if ($goal->tracking_mode === 'subcategory' && $goal->subcategory_id) {
            $query->where('subcategory_id', $goal->subcategory_id);
        } elseif ($goal->category_id) {
            $query->where('category_id', $goal->category_id);
        }

        return (string) $query->sum('amount');
    }

    private function projectCompletionDate(string $remaining, ?string $monthlyContribution): ?Carbon
    {
        if ($monthlyContribution === null || DecimalMath::comp($monthlyContribution, '0', self::DECIMAL_PRECISION) <= 0) {
            return null;
        }

        if (DecimalMath::comp($remaining, '0', self::DECIMAL_PRECISION) <= 0) {
            return now();
        }

        $monthsNeeded = (int) ceil(
            (float) DecimalMath::div($remaining, $monthlyContribution, self::DECIMAL_PRECISION)
        );

        return now()->addMonths(max(1, $monthsNeeded));
    }

    private function requiredMonthlyToTargetDate(string $remaining, Carbon $targetDate): ?string
    {
        if (DecimalMath::comp($remaining, '0', self::DECIMAL_PRECISION) <= 0) {
            return '0';
        }

        $now = now()->startOfDay();
        if ($targetDate->lessThanOrEqualTo($now)) {
            return null;
        }

        $months = max(1, (int) ceil($now->diffInDays($targetDate) / 30));
        return DecimalMath::div($remaining, (string) $months, self::DECIMAL_PRECISION);
    }
}
