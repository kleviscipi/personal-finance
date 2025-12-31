<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use Illuminate\Database\Seeder;

class OpeningBalanceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Note: This seeder can be called in two ways:
     * 1. Without parameters - seeds the opening balance category for ALL accounts
     * 2. With specific account - seeds the opening balance category for that account only
     */
    public function run(?int $accountId = null): void
    {
        $accounts = $accountId ? Account::whereKey($accountId)->get() : Account::all();

        foreach ($accounts as $account) {
            Category::firstOrCreate(
                [
                    'account_id' => $account->id,
                    'name' => 'Opening Balance',
                ],
                [
                    'type' => 'income',
                    'icon' => 'OB',
                    'color' => '#64748b',
                    'is_system' => true,
                    'order' => 0,
                ]
            );
        }
    }
}
