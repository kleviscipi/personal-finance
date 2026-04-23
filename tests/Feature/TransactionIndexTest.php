<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_pagination_preserves_active_filters(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'name' => 'Default Account',
            'base_currency' => 'USD',
            'description' => 'Test account',
            'is_active' => true,
        ]);

        $user->accounts()->attach($account->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $expenseCategory = Category::create([
            'account_id' => $account->id,
            'name' => 'Food',
            'type' => 'expense',
            'icon' => 'FD',
            'color' => '#22c55e',
            'order' => 1,
        ]);

        $incomeCategory = Category::create([
            'account_id' => $account->id,
            'name' => 'Salary',
            'type' => 'income',
            'icon' => 'IN',
            'color' => '#2563eb',
            'order' => 2,
        ]);

        foreach (range(1, 16) as $index) {
            Transaction::create([
                'account_id' => $account->id,
                'created_by' => $user->id,
                'type' => 'expense',
                'amount' => 10 + $index,
                'currency' => 'USD',
                'date' => now()->subDays($index)->toDateString(),
                'category_id' => $expenseCategory->id,
                'description' => "Expense {$index}",
                'payment_method' => 'card',
            ]);
        }

        Transaction::create([
            'account_id' => $account->id,
            'created_by' => $user->id,
            'type' => 'income',
            'amount' => 999,
            'currency' => 'USD',
            'date' => now()->toDateString(),
            'category_id' => $incomeCategory->id,
            'description' => 'Salary',
            'payment_method' => 'bank_transfer',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_account_id' => $account->id])
            ->get(route('transactions.index', [
                'type' => 'expense',
                'category_id' => $expenseCategory->id,
            ]));

        $response->assertOk();

        $page = $response->viewData('page');
        $transactions = $page['props']['transactions'];

        $this->assertSame('expense', $page['props']['filters']['type']);
        $this->assertSame((string) $expenseCategory->id, (string) $page['props']['filters']['category_id']);
        $this->assertStringContainsString('type=expense', $transactions['next_page_url']);
        $this->assertStringContainsString('category_id='.$expenseCategory->id, $transactions['next_page_url']);
    }
}
