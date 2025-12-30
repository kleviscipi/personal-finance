<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\Carbon;

class CurrencyService
{
    private array $supportedCurrencies = [
        'USD' => [
            'name' => 'US Dollar',
            'symbol' => '$',
            'code' => 'USD',
        ],
        'EUR' => [
            'name' => 'Euro',
            'symbol' => '€',
            'code' => 'EUR',
        ],
        'GBP' => [
            'name' => 'British Pound',
            'symbol' => '£',
            'code' => 'GBP',
        ],
        'JPY' => [
            'name' => 'Japanese Yen',
            'symbol' => '¥',
            'code' => 'JPY',
        ],
        'CHF' => [
            'name' => 'Swiss Franc',
            'symbol' => 'CHF',
            'code' => 'CHF',
        ],
        'CAD' => [
            'name' => 'Canadian Dollar',
            'symbol' => 'C$',
            'code' => 'CAD',
        ],
        'AUD' => [
            'name' => 'Australian Dollar',
            'symbol' => 'A$',
            'code' => 'AUD',
        ],
        'ALL' => [
            'name' => 'Albanian Lek',
            'symbol' => 'L',
            'code' => 'ALL',
        ],
    ];

    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    public function getCurrency(string $code): ?array
    {
        return $this->supportedCurrencies[$code] ?? null;
    }

    public function formatAmount(string $amount, string $currency): string
    {
        $currencyData = $this->getCurrency($currency);
        if (!$currencyData) {
            return $amount;
        }

        return $currencyData['symbol'] . number_format((float) $amount, 2);
    }

    /**
     * Convert amount from one currency to another using exchange rates
     * 
     * @param string|float $amount The amount to convert
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @param string|null $date Date for historical rate (defaults to today)
     * @return string Converted amount as string for precision
     */
    public function convert(
        string|float $amount,
        string $fromCurrency,
        string $toCurrency,
        ?string $date = null
    ): string {
        // If currencies are the same, no conversion needed
        if ($fromCurrency === $toCurrency) {
            return (string) $amount;
        }

        $date = $date ?? Carbon::today()->toDateString();
        
        // Try to find exchange rate for the given date
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency, $date);
        
        if ($rate === null) {
            // If no rate found for the date, try to find the most recent rate
            $rate = $this->getMostRecentRate($fromCurrency, $toCurrency);
        }
        
        if ($rate === null) {
            // If still no rate found, return original amount
            // In production, you might want to throw an exception or log a warning
            return (string) $amount;
        }

        // Perform conversion with high precision
        $converted = bcmul((string) $amount, (string) $rate, 4);
        
        return $converted;
    }

    /**
     * Get exchange rate for a specific date
     */
    public function getExchangeRate(string $from, string $to, string $date): ?string
    {
        $exchangeRate = ExchangeRate::where('base_currency', $from)
            ->where('target_currency', $to)
            ->where('date', $date)
            ->first();
            
        return $exchangeRate?->rate;
    }

    /**
     * Get the most recent exchange rate available
     */
    public function getMostRecentRate(string $from, string $to): ?string
    {
        $exchangeRate = ExchangeRate::where('base_currency', $from)
            ->where('target_currency', $to)
            ->orderBy('date', 'desc')
            ->first();
            
        return $exchangeRate?->rate;
    }

    /**
     * Store an exchange rate
     */
    public function storeExchangeRate(
        string $baseCurrency,
        string $targetCurrency,
        string $rate,
        string $date,
        string $source = 'manual'
    ): ExchangeRate {
        return ExchangeRate::updateOrCreate(
            [
                'base_currency' => $baseCurrency,
                'target_currency' => $targetCurrency,
                'date' => $date,
            ],
            [
                'rate' => $rate,
                'source' => $source,
            ]
        );
    }

    /**
     * Get all supported currency codes
     */
    public function getSupportedCurrencyCodes(): array
    {
        return array_keys($this->supportedCurrencies);
    }
}
