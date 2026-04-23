<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Tag;
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

    public function test_repeat_transaction_prefills_the_create_form(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'name' => 'Default Account',
            'base_currency' => 'ALL',
            'description' => 'Test account',
            'is_active' => true,
        ]);

        $user->accounts()->attach($account->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $category = Category::create([
            'account_id' => $account->id,
            'name' => 'Health',
            'type' => 'expense',
            'icon' => 'HT',
            'color' => '#14b8a6',
            'order' => 1,
        ]);

        $subcategory = Subcategory::create([
            'category_id' => $category->id,
            'name' => 'Gym',
            'order' => 1,
        ]);

        $tag = Tag::create([
            'account_id' => $account->id,
            'name' => 'monthly',
        ]);

        $transaction = Transaction::create([
            'account_id' => $account->id,
            'created_by' => $user->id,
            'type' => 'expense',
            'amount' => 2500,
            'currency' => 'ALL',
            'date' => '2026-04-01',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'description' => 'Gym membership',
            'payment_method' => 'card',
        ]);
        $transaction->tags()->attach($tag->id);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_account_id' => $account->id])
            ->get(route('transactions.create', [
                'repeat' => $transaction->id,
            ]));

        $response->assertOk();

        $page = $response->viewData('page');
        $initialDraft = $page['props']['initialDraft'];

        $this->assertSame($transaction->id, $initialDraft['source_transaction_id']);
        $this->assertSame('Gym membership', $initialDraft['source_description']);
        $this->assertSame('expense', $initialDraft['type']);
        $this->assertSame('2500.0000', $initialDraft['amount']);
        $this->assertSame('ALL', $initialDraft['currency']);
        $this->assertSame(now()->toDateString(), $initialDraft['date']);
        $this->assertSame($category->id, $initialDraft['category_id']);
        $this->assertSame($subcategory->id, $initialDraft['subcategory_id']);
        $this->assertSame('Gym membership', $initialDraft['description']);
        $this->assertSame('card', $initialDraft['payment_method']);
        $this->assertSame(['monthly'], $initialDraft['tag_list']);
    }
}
