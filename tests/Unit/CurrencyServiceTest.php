<?php

namespace Tests\Unit;

use App\Models\ExchangeRate;
use App\Services\CurrencyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyServiceTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyService $currencyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currencyService = new CurrencyService();
    }

    public function test_get_supported_currencies_returns_array(): void
    {
        $currencies = $this->currencyService->getSupportedCurrencies();

        $this->assertIsArray($currencies);
        $this->assertArrayHasKey('USD', $currencies);
        $this->assertArrayHasKey('EUR', $currencies);
        $this->assertArrayHasKey('GBP', $currencies);
    }

    public function test_get_currency_returns_currency_data(): void
    {
        $usd = $this->currencyService->getCurrency('USD');

        $this->assertIsArray($usd);
        $this->assertEquals('USD', $usd['code']);
        $this->assertEquals('US Dollar', $usd['name']);
        $this->assertEquals('$', $usd['symbol']);
    }

    public function test_get_currency_returns_null_for_unknown_currency(): void
    {
        $unknown = $this->currencyService->getCurrency('XYZ');

        $this->assertNull($unknown);
    }

    public function test_format_amount_returns_formatted_string(): void
    {
        $formatted = $this->currencyService->formatAmount('1234.56', 'USD');

        $this->assertEquals('$1,234.56', $formatted);
    }

    public function test_convert_same_currency_returns_same_amount(): void
    {
        $result = $this->currencyService->convert('100', 'USD', 'USD');

        $this->assertEquals('100', $result);
    }

    public function test_convert_with_exchange_rate(): void
    {
        $today = Carbon::today()->toDateString();

        // Create an exchange rate
        ExchangeRate::create([
            'base_currency' => 'USD',
            'target_currency' => 'EUR',
            'rate' => '0.92',
            'date' => $today,
            'source' => 'test',
        ]);

        $result = $this->currencyService->convert('100', 'USD', 'EUR', $today);

        // 100 USD * 0.92 = 92 EUR
        $this->assertEquals('92.0000', $result);
    }

    public function test_convert_uses_most_recent_rate_when_exact_date_not_found(): void
    {
        $yesterday = Carbon::yesterday()->toDateString();
        $today = Carbon::today()->toDateString();

        // Create an exchange rate for yesterday
        ExchangeRate::create([
            'base_currency' => 'USD',
            'target_currency' => 'EUR',
            'rate' => '0.90',
            'date' => $yesterday,
            'source' => 'test',
        ]);

        // Try to convert using today's date (no rate exists for today)
        $result = $this->currencyService->convert('100', 'USD', 'EUR', $today);

        // Should use yesterday's rate: 100 USD * 0.90 = 90 EUR
        $this->assertEquals('90.0000', $result);
    }

    public function test_store_exchange_rate_creates_new_rate(): void
    {
        $today = Carbon::today()->toDateString();

        $this->currencyService->storeExchangeRate(
            'USD',
            'GBP',
            '0.79',
            $today,
            'test'
        );

        $this->assertDatabaseHas('exchange_rates', [
            'base_currency' => 'USD',
            'target_currency' => 'GBP',
            'rate' => '0.79',
            'date' => $today,
            'source' => 'test',
        ]);
    }

    public function test_store_exchange_rate_updates_existing_rate(): void
    {
        $today = Carbon::today()->toDateString();

        // Create initial rate
        ExchangeRate::create([
            'base_currency' => 'USD',
            'target_currency' => 'JPY',
            'rate' => '149.00',
            'date' => $today,
            'source' => 'test',
        ]);

        // Update with new rate
        $this->currencyService->storeExchangeRate(
            'USD',
            'JPY',
            '150.50',
            $today,
            'updated'
        );

        // Should have only one record with updated values
        $this->assertDatabaseCount('exchange_rates', 1);
        $this->assertDatabaseHas('exchange_rates', [
            'base_currency' => 'USD',
            'target_currency' => 'JPY',
            'rate' => '150.50',
            'date' => $today,
            'source' => 'updated',
        ]);
    }

    public function test_get_exchange_rate_returns_rate_for_date(): void
    {
        $today = Carbon::today()->toDateString();

        ExchangeRate::create([
            'base_currency' => 'EUR',
            'target_currency' => 'USD',
            'rate' => '1.09',
            'date' => $today,
            'source' => 'test',
        ]);

        $rate = $this->currencyService->getExchangeRate('EUR', 'USD', $today);

        $this->assertEquals('1.09', $rate);
    }

    public function test_get_exchange_rate_returns_null_when_not_found(): void
    {
        $rate = $this->currencyService->getExchangeRate('XXX', 'YYY', '2024-01-01');

        $this->assertNull($rate);
    }

    public function test_get_supported_currency_codes_returns_array_of_codes(): void
    {
        $codes = $this->currencyService->getSupportedCurrencyCodes();

        $this->assertIsArray($codes);
        $this->assertContains('USD', $codes);
        $this->assertContains('EUR', $codes);
        $this->assertContains('GBP', $codes);
        $this->assertContains('JPY', $codes);
    }
}
