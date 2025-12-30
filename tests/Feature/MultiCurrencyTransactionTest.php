<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\ExchangeRate;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CurrencyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiCurrencyTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $account;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user with an account
        $this->user = User::factory()->create();
        $this->account = Account::create([
            'name' => 'Test Account',
            'base_currency' => 'USD',
        ]);

        // Attach user to account
        $this->account->users()->attach($this->user->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Create a category
        $this->category = Category::create([
            'account_id' => $this->account->id,
            'name' => 'Test Category',
            'type' => 'expense',
        ]);

        // Set up exchange rates
        $today = Carbon::today()->toDateString();
        ExchangeRate::create([
            'base_currency' => 'EUR',
            'target_currency' => 'USD',
            'rate' => '1.09',
            'date' => $today,
            'source' => 'test',
        ]);
    }

    public function test_can_create_transaction_in_different_currency(): void
    {
        $this->actingAs($this->user);

        // Set active account
        session(['active_account_id' => $this->account->id]);

        $response = $this->post(route('transactions.store'), [
            'type' => 'expense',
            'amount' => '100.00',
            'currency' => 'EUR',
            'date' => Carbon::today()->toDateString(),
            'category_id' => $this->category->id,
            'description' => 'Test EUR transaction',
        ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'account_id' => $this->account->id,
            'amount' => '100.00',
            'currency' => 'EUR',
            'description' => 'Test EUR transaction',
        ]);
    }

    public function test_transaction_with_unsupported_currency_is_rejected(): void
    {
        $this->actingAs($this->user);
        session(['active_account_id' => $this->account->id]);

        $response = $this->post(route('transactions.store'), [
            'type' => 'expense',
            'amount' => '100.00',
            'currency' => 'XYZ', // Invalid currency
            'date' => Carbon::today()->toDateString(),
            'category_id' => $this->category->id,
        ]);

        $response->assertSessionHasErrors('currency');
    }

    public function test_currency_service_converts_transaction_amount(): void
    {
        $currencyService = app(CurrencyService::class);
        $today = Carbon::today()->toDateString();

        // Convert 100 EUR to USD (rate: 1.09)
        $converted = $currencyService->convert('100', 'EUR', 'USD', $today);

        // 100 EUR * 1.09 = 109 USD
        $this->assertEquals('109.0000', $converted);
    }

    public function test_transaction_in_base_currency_requires_no_conversion(): void
    {
        $currencyService = app(CurrencyService::class);

        // Transaction in USD, account base is USD
        $converted = $currencyService->convert('100', 'USD', 'USD');

        $this->assertEquals('100', $converted);
    }

    public function test_can_list_transactions_with_different_currencies(): void
    {
        $this->actingAs($this->user);
        session(['active_account_id' => $this->account->id]);

        // Create transactions in different currencies
        Transaction::create([
            'account_id' => $this->account->id,
            'created_by' => $this->user->id,
            'type' => 'expense',
            'amount' => '100.00',
            'currency' => 'USD',
            'date' => Carbon::today(),
            'category_id' => $this->category->id,
        ]);

        Transaction::create([
            'account_id' => $this->account->id,
            'created_by' => $this->user->id,
            'type' => 'expense',
            'amount' => '50.00',
            'currency' => 'EUR',
            'date' => Carbon::today(),
            'category_id' => $this->category->id,
        ]);

        $response = $this->get(route('transactions.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Transactions/Index')
                ->has('transactions.data', 2)
        );
    }

    public function test_account_can_have_different_base_currency(): void
    {
        $eurAccount = Account::create([
            'name' => 'EUR Account',
            'base_currency' => 'EUR',
        ]);

        $eurAccount->users()->attach($this->user->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->assertEquals('EUR', $eurAccount->base_currency);
        $this->assertNotEquals($this->account->base_currency, $eurAccount->base_currency);
    }

    public function test_exchange_rate_can_be_created(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('exchange-rates.store'), [
            'base_currency' => 'USD',
            'target_currency' => 'GBP',
            'rate' => '0.79',
            'date' => Carbon::today()->toDateString(),
            'source' => 'manual',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('exchange_rates', [
            'base_currency' => 'USD',
            'target_currency' => 'GBP',
            'rate' => '0.79',
        ]);
    }

    public function test_exchange_rate_with_invalid_currency_is_rejected(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('exchange-rates.store'), [
            'base_currency' => 'XYZ',
            'target_currency' => 'ABC',
            'rate' => '1.0',
            'date' => Carbon::today()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['base_currency', 'target_currency']);
    }

    public function test_exchange_rate_with_same_base_and_target_is_rejected(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('exchange-rates.store'), [
            'base_currency' => 'USD',
            'target_currency' => 'USD',
            'rate' => '1.0',
            'date' => Carbon::today()->toDateString(),
        ]);

        $response->assertSessionHasErrors('target_currency');
    }

    public function test_exchange_rate_can_be_deleted(): void
    {
        $this->actingAs($this->user);

        $rate = ExchangeRate::create([
            'base_currency' => 'USD',
            'target_currency' => 'JPY',
            'rate' => '149.50',
            'date' => Carbon::today()->toDateString(),
            'source' => 'test',
        ]);

        $response = $this->delete(route('exchange-rates.destroy', $rate));

        $response->assertRedirect();

        $this->assertDatabaseMissing('exchange_rates', [
            'id' => $rate->id,
        ]);
    }
}
