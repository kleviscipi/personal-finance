<?php

namespace App\Services;

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
     * Convert amount from one currency to another
     * This is a placeholder for future FX implementation
     * In production, this would call an external FX API
     */
    public function convert(string $amount, string $fromCurrency, string $toCurrency): string
    {
        // For now, return the same amount
        // In future, implement actual FX conversion with historical rates
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        // Placeholder: Would call FX API here
        return $amount;
    }
}
