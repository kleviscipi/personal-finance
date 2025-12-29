<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Budget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    public function createBudget(Account $account, array $data): Budget
    {
        return Budget::create([
            'account_id' => $account->id,
            'category_id' => $data['category_id'] ?? null,
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? $account->base_currency,
            'period' => $data['period'] ?? 'monthly',
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'settings' => $data['settings'] ?? null,
        ]);
    }

    public function updateBudget(Budget $budget, array $data): Budget
    {
        $budget->update($data);
        return $budget->fresh();
    }

    public function deleteBudget(Budget $budget): bool
    {
        return $budget->delete();
    }

    public function calculateBudgetProgress(Budget $budget, ?Carbon $date = null): array
    {
        $date = $date ?? now();
        
        $spent = DB::table('transactions')
            ->where('account_id', $budget->account_id)
            ->where('type', 'expense')
            ->when($budget->category_id, function ($query) use ($budget) {
                return $query->where('category_id', $budget->category_id);
            })
            ->when($budget->subcategory_id, function ($query) use ($budget) {
                return $query->where('subcategory_id', $budget->subcategory_id);
            })
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->whereNull('deleted_at')
            ->sum('amount');
        
        $remaining = bcsub($budget->amount, $spent, 4);
        $percentage = $budget->amount > 0 
            ? bcmul(bcdiv($spent, $budget->amount, 4), 100, 2)
            : 0;
        
        return [
            'budget_amount' => $budget->amount,
            'spent' => $spent,
            'remaining' => $remaining,
            'percentage' => $percentage,
            'is_overspent' => bccomp($spent, $budget->amount, 4) > 0,
        ];
    }
}
