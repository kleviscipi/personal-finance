<?php

namespace App\Services;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getMonthlyExpensesByCategory(Account $account, ?string $month = null, ?string $year = null): array
    {
        $month = $month ?? now()->format('m');
        $year = $year ?? now()->format('Y');
        
        return DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.account_id', $account->id)
            ->where('transactions.type', 'expense')
            ->whereYear('transactions.date', $year)
            ->whereMonth('transactions.date', $month)
            ->whereNull('transactions.deleted_at')
            ->select(
                'categories.name as category',
                'categories.icon',
                'categories.color',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.icon', 'categories.color')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    public function getMonthlyIncome(Account $account, ?string $month = null, ?string $year = null): string
    {
        $month = $month ?? now()->format('m');
        $year = $year ?? now()->format('Y');
        
        return DB::table('transactions')
            ->where('account_id', $account->id)
            ->where('type', 'income')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNull('deleted_at')
            ->sum('amount');
    }

    public function getMonthlyExpenses(Account $account, ?string $month = null, ?string $year = null): string
    {
        $month = $month ?? now()->format('m');
        $year = $year ?? now()->format('Y');
        
        return DB::table('transactions')
            ->where('account_id', $account->id)
            ->where('type', 'expense')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNull('deleted_at')
            ->sum('amount');
    }

    public function getNetCashFlow(Account $account, ?string $month = null, ?string $year = null): string
    {
        $income = $this->getMonthlyIncome($account, $month, $year);
        $expenses = $this->getMonthlyExpenses($account, $month, $year);
        
        return bcsub($income, $expenses, 4);
    }

    public function getBudgetUsage(Account $account, ?string $month = null, ?string $year = null): array
    {
        $month = $month ?? now()->format('m');
        $year = $year ?? now()->format('Y');
        $date = Carbon::parse("$year-$month-01");
        
        return DB::table('budgets')
            ->leftJoin('categories', 'budgets.category_id', '=', 'categories.id')
            ->leftJoin('transactions', function ($join) use ($date) {
                $join->on('transactions.category_id', '=', 'budgets.category_id')
                    ->whereYear('transactions.date', $date->year)
                    ->whereMonth('transactions.date', $date->month)
                    ->where('transactions.type', 'expense')
                    ->whereNull('transactions.deleted_at');
            })
            ->where('budgets.account_id', $account->id)
            ->where('budgets.period', 'monthly')
            ->where('budgets.start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('budgets.end_date')
                    ->orWhere('budgets.end_date', '>=', $date);
            })
            ->whereNull('budgets.deleted_at')
            ->select(
                'budgets.id',
                'categories.name as category',
                'budgets.amount as budget',
                DB::raw('COALESCE(SUM(transactions.amount), 0) as spent'),
                DB::raw('budgets.amount - COALESCE(SUM(transactions.amount), 0) as remaining')
            )
            ->groupBy('budgets.id', 'categories.name', 'budgets.amount')
            ->get()
            ->toArray();
    }

    public function getCategoryTrends(Account $account, int $months = 6): array
    {
        $startDate = now()->subMonths($months);
        
        return DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.account_id', $account->id)
            ->where('transactions.type', 'expense')
            ->where('transactions.date', '>=', $startDate)
            ->whereNull('transactions.deleted_at')
            ->select(
                'categories.name as category',
                DB::raw('DATE_FORMAT(transactions.date, "%Y-%m") as month'),
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('categories.id', 'categories.name', DB::raw('DATE_FORMAT(transactions.date, "%Y-%m")'))
            ->orderBy(DB::raw('DATE_FORMAT(transactions.date, "%Y-%m")'))
            ->get()
            ->groupBy('category')
            ->toArray();
    }

    public function getDashboardData(Account $account): array
    {
        $currentMonth = now()->format('m');
        $currentYear = now()->format('Y');
        
        return [
            'current_month_expenses' => $this->getMonthlyExpenses($account, $currentMonth, $currentYear),
            'current_month_income' => $this->getMonthlyIncome($account, $currentMonth, $currentYear),
            'net_cash_flow' => $this->getNetCashFlow($account, $currentMonth, $currentYear),
            'expenses_by_category' => $this->getMonthlyExpensesByCategory($account, $currentMonth, $currentYear),
            'budget_usage' => $this->getBudgetUsage($account, $currentMonth, $currentYear),
            'category_trends' => $this->getCategoryTrends($account, 6),
        ];
    }
}
