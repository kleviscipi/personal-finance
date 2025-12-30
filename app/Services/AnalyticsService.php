<?php

namespace App\Services;

use App\Models\Account;
use App\Support\DecimalMath;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    private function excludeOpeningBalance($query, string $table = 'transactions'): void
    {
        $query->where(function ($inner) use ($table) {
            $inner->whereNull("{$table}.metadata")
                ->orWhereRaw("({$table}.metadata->>'opening_balance')::boolean IS DISTINCT FROM true");
        });
    }

    public function getStatisticsRange(Account $account, string $startDate, string $endDate): array
    {
        $rangeStart = Carbon::parse($startDate)->startOfMonth();
        $rangeEnd = Carbon::parse($endDate)->startOfMonth();
        $months = [];
        $cursor = $rangeStart->copy();
        while ($cursor <= $rangeEnd) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $monthly = DB::table('transactions')
            ->where('account_id', $account->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->tap(function ($query) {
                $this->excludeOpeningBalance($query);
            })
            ->select(
                DB::raw("to_char(date_trunc('month', date), 'YYYY-MM') as month"),
                DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses"),
                DB::raw("SUM(CASE WHEN type = 'transfer' THEN amount ELSE 0 END) as transfers")
            )
            ->groupBy(DB::raw("date_trunc('month', date)"))
            ->orderBy(DB::raw("date_trunc('month', date)"))
            ->get()
            ->toArray();

        $monthlyMap = collect($monthly)->keyBy('month');
        $monthlySummary = array_map(function ($month) use ($monthlyMap) {
            $row = $monthlyMap->get($month);
            $income = $row->income ?? 0;
            $expenses = $row->expenses ?? 0;
            $transfers = $row->transfers ?? 0;

            return [
                'month' => $month,
                'income' => $income,
                'expenses' => $expenses,
                'transfers' => $transfers,
                'net' => DecimalMath::sub($income, $expenses, 4),
            ];
        }, $months);

        $expenseByMonth = array_map(function ($row) {
            return [
                'month' => $row['month'],
                'total' => $row['expenses'],
            ];
        }, $monthlySummary);

        $topCategories = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.account_id', $account->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->whereNull('transactions.deleted_at')
            ->select(
                'categories.id as category_id',
                'categories.name as category',
                'categories.color as color',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->toArray();

        $topSubcategoriesRows = DB::table('transactions')
            ->join('subcategories', 'transactions.subcategory_id', '=', 'subcategories.id')
            ->join('categories', 'subcategories.category_id', '=', 'categories.id')
            ->where('transactions.account_id', $account->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->whereNull('transactions.deleted_at')
            ->select(
                'subcategories.id as subcategory_id',
                'subcategories.name as subcategory',
                'categories.name as category',
                'categories.color as color',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('subcategories.id', 'subcategories.name', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->toArray();

        $topSubcategories = collect($topSubcategoriesRows)->map(function ($row) {
            $label = $row->category ? "{$row->category} • {$row->subcategory}" : $row->subcategory;
            return [
                'subcategory' => $row->subcategory,
                'category' => $row->category,
                'label' => $label,
                'color' => $row->color,
                'total' => $row->total,
            ];
        })->toArray();

        $topCategoryIds = collect($topCategories)->pluck('category_id')->filter()->values();
        $categoryMonthlyRows = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.account_id', $account->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->whereNull('transactions.deleted_at')
            ->when($topCategoryIds->isNotEmpty(), function ($query) use ($topCategoryIds) {
                $query->whereIn('transactions.category_id', $topCategoryIds);
            })
            ->select(
                'categories.id as category_id',
                'categories.name as category',
                'categories.color as color',
                DB::raw("to_char(date_trunc('month', transactions.date), 'YYYY-MM') as month"),
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.color', DB::raw("date_trunc('month', transactions.date)"))
            ->get()
            ->toArray();

        $topSubcategoryIds = collect($topSubcategoriesRows)->pluck('subcategory_id')->filter()->values();
        $subcategoryMonthlyRows = DB::table('transactions')
            ->join('subcategories', 'transactions.subcategory_id', '=', 'subcategories.id')
            ->join('categories', 'subcategories.category_id', '=', 'categories.id')
            ->where('transactions.account_id', $account->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->whereNull('transactions.deleted_at')
            ->when($topSubcategoryIds->isNotEmpty(), function ($query) use ($topSubcategoryIds) {
                $query->whereIn('transactions.subcategory_id', $topSubcategoryIds);
            })
            ->select(
                'subcategories.id as subcategory_id',
                'subcategories.name as subcategory',
                'categories.name as category',
                'categories.color as color',
                DB::raw("to_char(date_trunc('month', transactions.date), 'YYYY-MM') as month"),
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy(
                'subcategories.id',
                'subcategories.name',
                'categories.name',
                'categories.color',
                DB::raw("date_trunc('month', transactions.date)")
            )
            ->get()
            ->toArray();

        $categoryById = [];
        foreach ($topCategories as $category) {
            $categoryById[$category->category_id] = [
                'category' => $category->category,
                'color' => $category->color,
            ];
        }

        $subcategoryById = [];
        foreach ($topSubcategoriesRows as $subcategory) {
            $label = $subcategory->category ? "{$subcategory->category} • {$subcategory->subcategory}" : $subcategory->subcategory;
            $subcategoryById[$subcategory->subcategory_id] = [
                'subcategory' => $subcategory->subcategory,
                'category' => $subcategory->category,
                'label' => $label,
                'color' => $subcategory->color,
            ];
        }

        $categoryMonthMap = [];
        foreach ($categoryMonthlyRows as $row) {
            $categoryMonthMap[$row->category_id][$row->month] = $row->total;
        }

        $subcategoryMonthMap = [];
        foreach ($subcategoryMonthlyRows as $row) {
            $subcategoryMonthMap[$row->subcategory_id][$row->month] = $row->total;
        }

        $categorySeries = [];
        foreach ($categoryById as $categoryId => $info) {
            $values = [];
            foreach ($months as $month) {
                $values[] = $categoryMonthMap[$categoryId][$month] ?? 0;
            }
            $categorySeries[] = [
                'category' => $info['category'],
                'color' => $info['color'],
                'values' => $values,
            ];
        }

        $subcategorySeries = [];
        foreach ($subcategoryById as $subcategoryId => $info) {
            $values = [];
            foreach ($months as $month) {
                $values[] = $subcategoryMonthMap[$subcategoryId][$month] ?? 0;
            }
            $subcategorySeries[] = [
                'subcategory' => $info['subcategory'],
                'category' => $info['category'],
                'label' => $info['label'],
                'color' => $info['color'],
                'values' => $values,
            ];
        }

        $expenseShareSeries = [];
        foreach ($categorySeries as $series) {
            $shareValues = [];
            foreach ($series['values'] as $index => $value) {
                $monthTotal = $expenseByMonth[$index]['total'] ?? 0;
                $shareValues[] = $monthTotal > 0 ? round(($value / $monthTotal) * 100, 1) : 0;
            }
            $expenseShareSeries[] = [
                'category' => $series['category'],
                'color' => $series['color'],
                'values' => $shareValues,
            ];
        }

        $expenseTotals = array_map(function ($row) {
            return (float) $row['expenses'];
        }, $monthlySummary);
        sort($expenseTotals);
        $count = count($expenseTotals);
        $medianExpense = 0;
        if ($count > 0) {
            $middle = (int) floor(($count - 1) / 2);
            if ($count % 2) {
                $medianExpense = $expenseTotals[$middle];
            } else {
                $medianExpense = ($expenseTotals[$middle] + $expenseTotals[$middle + 1]) / 2;
            }
        }

        $totals = DB::table('transactions')
            ->where('account_id', $account->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->tap(function ($query) {
                $this->excludeOpeningBalance($query);
            })
            ->select(
                DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses"),
                DB::raw("SUM(CASE WHEN type = 'transfer' THEN amount ELSE 0 END) as transfers")
            )
            ->first();

        $openingBalance = DB::table('transactions')
            ->where('account_id', $account->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->whereRaw("(metadata->>'opening_balance')::boolean = true")
            ->sum('amount');

        return [
            'monthly_summary' => $monthlySummary,
            'top_categories' => $topCategories,
            'top_subcategories' => $topSubcategories,
            'expense_by_month' => $expenseByMonth,
            'category_mix' => [
                'months' => $months,
                'series' => $categorySeries,
            ],
            'subcategory_mix' => [
                'months' => $months,
                'series' => $subcategorySeries,
            ],
            'expense_share' => [
                'months' => $months,
                'series' => $expenseShareSeries,
            ],
            'median_expense' => $medianExpense,
            'totals' => [
                'income' => $totals->income ?? 0,
                'expenses' => $totals->expenses ?? 0,
                'transfers' => $totals->transfers ?? 0,
                'net' => DecimalMath::sub($totals->income ?? 0, $totals->expenses ?? 0, 4),
                'opening_balance' => $openingBalance,
            ],
        ];
    }
    public function getMonthlySummary(Account $account, int $months = 12): array
    {
        $startDate = now()->subMonths($months - 1)->startOfMonth();
        $endDate = now()->startOfMonth();
        $monthsList = [];
        $cursor = $startDate->copy();
        while ($cursor <= $endDate) {
            $monthsList[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $rows = DB::table('transactions')
            ->where('account_id', $account->id)
            ->where('date', '>=', $startDate)
            ->whereNull('deleted_at')
            ->tap(function ($query) {
                $this->excludeOpeningBalance($query);
            })
            ->select(
                DB::raw("to_char(date_trunc('month', date), 'YYYY-MM') as month"),
                DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses")
            )
            ->groupBy(DB::raw("date_trunc('month', date)"))
            ->orderBy(DB::raw("date_trunc('month', date)"))
            ->get()
            ->toArray();

        $rowMap = collect($rows)->keyBy('month');

        return array_map(function ($month) use ($rowMap) {
            $row = $rowMap->get($month);
            $income = $row->income ?? 0;
            $expenses = $row->expenses ?? 0;

            return [
                'month' => $month,
                'income' => $income,
                'expenses' => $expenses,
                'net' => DecimalMath::sub($income, $expenses, 4),
            ];
        }, $monthsList);
    }

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
            ->tap(function ($query) {
                $this->excludeOpeningBalance($query);
            })
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
            ->tap(function ($query) {
                $this->excludeOpeningBalance($query);
            })
            ->sum('amount');
    }

    public function getNetCashFlow(Account $account, ?string $month = null, ?string $year = null): string
    {
        $income = $this->getMonthlyIncome($account, $month, $year);
        $expenses = $this->getMonthlyExpenses($account, $month, $year);
        
        return DecimalMath::sub($income, $expenses, 4);
    }

    public function getBudgetUsage(Account $account, ?string $month = null, ?string $year = null): array
    {
        $month = $month ?? now()->format('m');
        $year = $year ?? now()->format('Y');
        $date = Carbon::parse("$year-$month-01");
        
        return DB::table('budgets')
            ->leftJoin('categories', 'budgets.category_id', '=', 'categories.id')
            ->leftJoin('transactions', function ($join) use ($date) {
                $join->on('transactions.account_id', '=', 'budgets.account_id')
                    ->whereYear('transactions.date', $date->year)
                    ->whereMonth('transactions.date', $date->month)
                    ->where('transactions.type', 'expense')
                    ->whereNull('transactions.deleted_at')
                    ->where(function ($query) {
                        $query->whereNull('budgets.category_id')
                            ->orWhereColumn('transactions.category_id', 'budgets.category_id');
                    })
                    ->where(function ($query) {
                        $query->whereNull('budgets.subcategory_id')
                            ->orWhereColumn('transactions.subcategory_id', 'budgets.subcategory_id');
                    });
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

    public function getBudgetVariance(Account $account, ?string $month = null, ?string $year = null): array
    {
        $month = $month ?? now()->format('m');
        $year = $year ?? now()->format('Y');
        $date = Carbon::parse("$year-$month-01");

        return DB::table('budgets')
            ->leftJoin('categories', 'budgets.category_id', '=', 'categories.id')
            ->leftJoin('transactions', function ($join) use ($date) {
                $join->on('transactions.account_id', '=', 'budgets.account_id')
                    ->whereYear('transactions.date', $date->year)
                    ->whereMonth('transactions.date', $date->month)
                    ->where('transactions.type', 'expense')
                    ->whereNull('transactions.deleted_at')
                    ->where(function ($query) {
                        $query->whereNull('budgets.category_id')
                            ->orWhereColumn('transactions.category_id', 'budgets.category_id');
                    })
                    ->where(function ($query) {
                        $query->whereNull('budgets.subcategory_id')
                            ->orWhereColumn('transactions.subcategory_id', 'budgets.subcategory_id');
                    });
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
                'categories.color as color',
                'budgets.amount as budget',
                DB::raw('COALESCE(SUM(transactions.amount), 0) as spent'),
                DB::raw('COALESCE(SUM(transactions.amount), 0) - budgets.amount as variance')
            )
            ->groupBy('budgets.id', 'categories.name', 'categories.color', 'budgets.amount')
            ->orderBy(DB::raw('COALESCE(SUM(transactions.amount), 0) - budgets.amount'), 'desc')
            ->get()
            ->toArray();
    }

    public function getTopCategories(Account $account, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $rows = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.account_id', $account->id)
            ->where('transactions.type', 'expense')
            ->where('transactions.date', '>=', $startDate)
            ->whereNull('transactions.deleted_at')
            ->select(
                'categories.name as category',
                'categories.color',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $total = $rows->sum('total') ?: 1;

        return $rows->map(function ($row) use ($total) {
            return [
                'category' => $row->category,
                'color' => $row->color,
                'total' => $row->total,
                'percentage' => round(($row->total / $total) * 100, 1),
            ];
        })->toArray();
    }

    public function getTopSubcategories(Account $account, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $rows = DB::table('transactions')
            ->join('subcategories', 'transactions.subcategory_id', '=', 'subcategories.id')
            ->join('categories', 'subcategories.category_id', '=', 'categories.id')
            ->where('transactions.account_id', $account->id)
            ->where('transactions.type', 'expense')
            ->where('transactions.date', '>=', $startDate)
            ->whereNull('transactions.deleted_at')
            ->select(
                'subcategories.name as subcategory',
                'categories.name as category',
                'categories.color',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('subcategories.id', 'subcategories.name', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $total = $rows->sum('total') ?: 1;

        return $rows->map(function ($row) use ($total) {
            $label = $row->category ? "{$row->category} • {$row->subcategory}" : $row->subcategory;
            return [
                'subcategory' => $row->subcategory,
                'category' => $row->category,
                'label' => $label,
                'color' => $row->color,
                'total' => $row->total,
                'percentage' => round(($row->total / $total) * 100, 1),
            ];
        })->toArray();
    }

    public function getSavingsRate(Account $account, ?string $month = null, ?string $year = null): array
    {
        $income = $this->getMonthlyIncome($account, $month, $year);
        $expenses = $this->getMonthlyExpenses($account, $month, $year);

        $rate = 0.0;
        if ((float) $income > 0) {
            $rate = round((float) DecimalMath::div(
                DecimalMath::sub($income, $expenses, 4),
                $income,
                4
            ) * 100, 2);
        }

        return [
            'income' => $income,
            'expenses' => $expenses,
            'rate' => $rate,
        ];
    }

    public function getForecast(Account $account): array
    {
        $startDate = now()->subDays(30);

        $income = DB::table('transactions')
            ->where('account_id', $account->id)
            ->where('type', 'income')
            ->where('date', '>=', $startDate)
            ->whereNull('deleted_at')
            ->tap(function ($query) {
                $this->excludeOpeningBalance($query);
            })
            ->sum('amount');

        $expenses = DB::table('transactions')
            ->where('account_id', $account->id)
            ->where('type', 'expense')
            ->where('date', '>=', $startDate)
            ->whereNull('deleted_at')
            ->tap(function ($query) {
                $this->excludeOpeningBalance($query);
            })
            ->sum('amount');

        $dailyIncome = DecimalMath::div($income, 30, 4);
        $dailyExpenses = DecimalMath::div($expenses, 30, 4);

        $forecast30 = [
            'income' => DecimalMath::mul($dailyIncome, 30, 4),
            'expenses' => DecimalMath::mul($dailyExpenses, 30, 4),
            'net' => DecimalMath::sub(
                DecimalMath::mul($dailyIncome, 30, 4),
                DecimalMath::mul($dailyExpenses, 30, 4),
                4
            ),
        ];

        $forecast90 = [
            'income' => DecimalMath::mul($dailyIncome, 90, 4),
            'expenses' => DecimalMath::mul($dailyExpenses, 90, 4),
            'net' => DecimalMath::sub(
                DecimalMath::mul($dailyIncome, 90, 4),
                DecimalMath::mul($dailyExpenses, 90, 4),
                4
            ),
        ];

        return [
            'last_30_days' => [
                'income' => $income,
                'expenses' => $expenses,
            ],
            'forecast_30' => $forecast30,
            'forecast_90' => $forecast90,
        ];
    }

    public function getCategorySpikes(Account $account): array
    {
        $recentStart = now()->subDays(30);
        $baselineStart = now()->subDays(120);
        $baselineEnd = now()->subDays(30);

        $recent = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.account_id', $account->id)
            ->where('transactions.type', 'expense')
            ->where('transactions.date', '>=', $recentStart)
            ->whereNull('transactions.deleted_at')
            ->select(
                'categories.id as category_id',
                'categories.name as category',
                'categories.color as color',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->get()
            ->keyBy('category_id');

        $baseline = DB::table('transactions')
            ->where('transactions.account_id', $account->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.date', [$baselineStart, $baselineEnd])
            ->whereNull('transactions.deleted_at')
            ->select(
                'transactions.category_id as category_id',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('transactions.category_id')
            ->get()
            ->keyBy('category_id');

        $spikes = [];
        foreach ($recent as $categoryId => $row) {
            $baselineTotal = $baseline[$categoryId]->total ?? 0;
            $baselineAvg = $baselineTotal / 3; // 90 days window
            if ($baselineAvg <= 0) {
                continue;
            }

            $delta = ($row->total - $baselineAvg) / $baselineAvg * 100;
            if ($delta >= 50) {
                $spikes[] = [
                    'category' => $row->category,
                    'color' => $row->color,
                    'recent_total' => $row->total,
                    'baseline' => $baselineAvg,
                    'delta_percent' => round($delta, 1),
                ];
            }
        }

        usort($spikes, function ($a, $b) {
            return $b['delta_percent'] <=> $a['delta_percent'];
        });

        return array_slice($spikes, 0, 5);
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
                DB::raw("to_char(transactions.date, 'YYYY-MM') as month"),
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('categories.id', 'categories.name', DB::raw("to_char(transactions.date, 'YYYY-MM')"))
            ->orderBy(DB::raw("to_char(transactions.date, 'YYYY-MM')"))
            ->get()
            ->groupBy('category')
            ->toArray();
    }

    /**
     * Get total balance (cumulative net worth from all transactions)
     */
    public function getTotalBalance(Account $account): string
    {
        $result = DB::table('transactions')
            ->where('account_id', $account->id)
            ->whereNull('deleted_at')
            ->select(
                DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expenses")
            )
            ->first();

        $totalIncome = $result->total_income ?? 0;
        $totalExpenses = $result->total_expenses ?? 0;

        return DecimalMath::sub($totalIncome, $totalExpenses, 4);
    }

    /**
     * Get balance history showing cumulative balance over time
     */
    public function getBalanceHistory(Account $account, int $months = 12): array
    {
        $startDate = now()->subMonths($months - 1)->startOfMonth();
        $endDate = now()->startOfMonth();
        $monthsList = [];
        $cursor = $startDate->copy();
        while ($cursor <= $endDate) {
            $monthsList[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        // Get monthly aggregates
        $monthlyData = DB::table('transactions')
            ->where('account_id', $account->id)
            ->where('date', '>=', $startDate)
            ->whereNull('deleted_at')
            ->select(
                DB::raw("to_char(date_trunc('month', date), 'YYYY-MM') as month"),
                DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses")
            )
            ->groupBy(DB::raw("date_trunc('month', date)"))
            ->orderBy(DB::raw("date_trunc('month', date)"))
            ->get();

        $monthlyMap = collect($monthlyData)->keyBy('month');

        // Get starting balance (all transactions before start date)
        $startingBalanceResult = DB::table('transactions')
            ->where('account_id', $account->id)
            ->where('date', '<', $startDate)
            ->whereNull('deleted_at')
            ->select(
                DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses")
            )
            ->first();

        $cumulativeBalance = DecimalMath::sub(
            $startingBalanceResult->income ?? 0,
            $startingBalanceResult->expenses ?? 0,
            4
        );

        $history = [];
        foreach ($monthsList as $month) {
            $row = $monthlyMap->get($month);
            $monthIncome = $row->income ?? 0;
            $monthExpenses = $row->expenses ?? 0;
            $monthSavings = DecimalMath::sub($monthIncome, $monthExpenses, 4);
            
            // Add monthly savings to cumulative balance
            $cumulativeBalance = DecimalMath::add($cumulativeBalance, $monthSavings, 4);

            $history[] = [
                'month' => $month,
                'income' => $monthIncome,
                'expenses' => $monthExpenses,
                'savings' => $monthSavings,
                'balance' => $cumulativeBalance,
            ];
        }

        return $history;
    }

    /**
     * Get current month savings (same as net cash flow but with context)
     */
    public function getCurrentMonthSavings(Account $account): array
    {
        $currentMonth = now()->format('m');
        $currentYear = now()->format('Y');
        
        $income = $this->getMonthlyIncome($account, $currentMonth, $currentYear);
        $expenses = $this->getMonthlyExpenses($account, $currentMonth, $currentYear);
        $savings = DecimalMath::sub($income, $expenses, 4);
        
        $savingsRate = 0.0;
        if ((float) $income > 0) {
            $savingsRate = round((float) DecimalMath::div($savings, $income, 4) * 100, 2);
        }

        return [
            'amount' => $savings,
            'rate' => $savingsRate,
            'income' => $income,
            'expenses' => $expenses,
        ];
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
            'budget_variance' => $this->getBudgetVariance($account, $currentMonth, $currentYear),
            'category_trends' => $this->getCategoryTrends($account, 6),
            'monthly_summary' => $this->getMonthlySummary($account, 12),
            'top_categories' => $this->getTopCategories($account, 30),
            'top_subcategories' => $this->getTopSubcategories($account, 30),
            'savings_rate' => $this->getSavingsRate($account, $currentMonth, $currentYear),
            'forecast' => $this->getForecast($account),
            'category_spikes' => $this->getCategorySpikes($account),
            'total_balance' => $this->getTotalBalance($account),
            'balance_history' => $this->getBalanceHistory($account, 12),
            'current_month_savings' => $this->getCurrentMonthSavings($account),
        ];
    }
}
