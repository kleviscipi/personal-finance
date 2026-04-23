<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\TransactionHistory;
use App\Models\User;
use App\Services\RecurringTransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_monthly_recurring_transactions_are_generated_and_advance_correctly(): void
    {
        [$user, $account] = $this->makeUserWithAccount();
        $member = User::factory()->create();
        $member->accounts()->attach($account->id, [
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        [$category, $subcategory, $tag] = $this->makeTransactionFixtures($account);

        $service = app(RecurringTransactionService::class);
        $recurring = $service->createRecurringTransaction($account, $user, [
            'user_id' => $member->id,
            'type' => 'expense',
            'amount' => '2500.00',
            'currency' => 'ALL',
            'frequency' => 'monthly',
            'interval' => 1,
            'next_run_date' => '2026-01-31',
            'end_date' => null,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'description' => 'Gym membership',
            'payment_method' => 'card',
            'is_active' => true,
            'tag_ids' => [$tag->id],
            'tag_names' => [],
        ]);

        $result = $service->runDueTransactions(Carbon::parse('2026-03-31'), $account);

        $this->assertSame(1, $result['templates_processed']);
        $this->assertSame(3, $result['transactions_created']);

        $transactions = Transaction::query()
            ->where('account_id', $account->id)
            ->orderBy('date')
            ->get();

        $this->assertCount(3, $transactions);
        $this->assertSame([$member->id, $member->id, $member->id], $transactions->pluck('created_by')->all());
        $this->assertSame(
            ['2026-01-31', '2026-02-28', '2026-03-31'],
            $transactions->pluck('date')->map(fn ($date) => $date->toDateString())->all()
        );
        $this->assertSame(
            [$recurring->id, $recurring->id, $recurring->id],
            $transactions->pluck('metadata.recurring_transaction_id')->all()
        );
        $this->assertEquals([$tag->id], $transactions->first()->tags()->pluck('tags.id')->all());
        $this->assertSame([$user->id, $user->id, $user->id], TransactionHistory::query()->pluck('changed_by')->all());

        $recurring->refresh();
        $this->assertSame('2026-04-30', $recurring->next_run_date->toDateString());
        $this->assertNotNull($recurring->last_generated_at);
        $this->assertTrue($recurring->is_active);
    }

    public function test_run_due_endpoint_creates_transactions_for_active_account(): void
    {
        [$user, $account] = $this->makeUserWithAccount();
        $member = User::factory()->create();
        $member->accounts()->attach($account->id, [
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        [$category, $subcategory] = $this->makeTransactionFixtures($account);

        RecurringTransaction::create([
            'account_id' => $account->id,
            'created_by' => $user->id,
            'user_id' => $member->id,
            'type' => 'expense',
            'amount' => 40,
            'currency' => 'ALL',
            'next_run_date' => '2026-04-20',
            'frequency' => 'weekly',
            'interval' => 1,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'description' => 'Weekly class',
            'payment_method' => 'card',
            'is_active' => true,
        ]);

        $this->travelTo(Carbon::parse('2026-04-23 09:00:00'));

        $this->actingAs($user)
            ->withSession(['active_account_id' => $account->id])
            ->post(route('recurring-transactions.run-due'))
            ->assertRedirect(route('recurring-transactions.index'))
            ->assertSessionHas('message');

        $this->assertDatabaseCount('transactions', 1);
        $transaction = Transaction::query()->firstOrFail();
        $this->assertSame($account->id, $transaction->account_id);
        $this->assertSame($member->id, $transaction->created_by);
        $this->assertSame('Weekly class', $transaction->description);
        $this->assertSame('2026-04-20', $transaction->date->toDateString());
    }

    private function makeUserWithAccount(): array
    {
        $user = User::factory()->create();
        $account = Account::create([
            'name' => 'Default Account',
            'base_currency' => 'ALL',
            'description' => 'Household account',
            'is_active' => true,
        ]);

        $user->accounts()->attach($account->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        return [$user, $account];
    }

    private function makeTransactionFixtures(Account $account): array
    {
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

        return [$category, $subcategory, $tag];
    }
}
