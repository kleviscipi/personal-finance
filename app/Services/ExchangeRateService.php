<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExchangeRateService
{
    private function baseUrl(): string
    {
        return config('services.exchangerate_host.base_url', 'https://api.exchangerate.host');
    }

    private function accessKey(): ?string
    {
        $key = config('services.exchangerate_host.access_key');
        return $key ? trim($key) : null;
    }

    private function endpoint(): string
    {
        $endpoint = config('services.exchangerate_host.endpoint', 'latest');
        return trim($endpoint ?: 'latest', '/');
    }

    public function getRate(string $date, string $fromCurrency, string $toCurrency): ?string
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return '1';
        }

        $rateDate = Carbon::parse($date)->toDateString();

        $rate = ExchangeRate::query()
            ->where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->where('rate_date', '<=', $rateDate)
            ->orderByDesc('rate_date')
            ->value('rate');

        if ($rate !== null) {
            return (string) $rate;
        }

        $inverse = ExchangeRate::query()
            ->where('from_currency', $toCurrency)
            ->where('to_currency', $fromCurrency)
            ->where('rate_date', '<=', $rateDate)
            ->orderByDesc('rate_date')
            ->value('rate');

        if ($inverse === null || (float) $inverse === 0.0) {
            return null;
        }

        return (string) (1 / (float) $inverse);
    }

    public function convert(string $amount, string $date, string $fromCurrency, string $toCurrency): string
    {
        $rate = $this->getRate($date, $fromCurrency, $toCurrency);
        if ($rate === null) {
            return $amount;
        }

        return (string) ((float) $amount * (float) $rate);
    }

    public function fetchRates(string $date, string $baseCurrency, array $symbols): array
    {
        $baseCurrency = strtoupper($baseCurrency);
        $symbols = array_values(array_unique(array_filter(array_map('strtoupper', $symbols))));

        if (empty($symbols)) {
            return [];
        }

        $endpointDate = $date === 'latest' ? 'latest' : Carbon::parse($date)->toDateString();
        $endpoint = $this->endpoint();
        $query = [
            'access_key' => $this->accessKey(),
        ];

        if ($endpoint === 'live') {
            $query['source'] = $baseCurrency;
            $query['currencies'] = implode(',', $symbols);
            $query['format'] = 1;
        } else {
            $query['base'] = $baseCurrency;
            $query['symbols'] = implode(',', $symbols);
        }

        $url = $endpoint === 'live'
            ? "{$this->baseUrl()}/live"
            : "{$this->baseUrl()}/{$endpointDate}";

        $response = Http::timeout(15)
            ->retry(2, 200)
            ->get($url, $query);

        if (!$response->ok()) {
            throw new RuntimeException('ExchangeRateHost request failed: ' . $response->status());
        }

        $payload = $response->json();
        if (($payload['success'] ?? true) === false) {
            $message = $payload['error']['type'] ?? 'unknown_error';
            $detail = $message === 'missing_access_key'
                ? ' (set EXCHANGERATE_HOST_ACCESS_KEY)'
                : '';
            throw new RuntimeException("ExchangeRateHost error: {$message}{$detail}");
        }

        if (isset($payload['rates']) && is_array($payload['rates'])) {
            return $payload['rates'];
        }

        if (isset($payload['quotes']) && is_array($payload['quotes'])) {
            $rates = [];
            foreach ($payload['quotes'] as $pair => $rate) {
                if (strpos($pair, $baseCurrency) !== 0) {
                    continue;
                }
                $symbol = substr($pair, strlen($baseCurrency));
                if ($symbol) {
                    $rates[$symbol] = $rate;
                }
            }
            return $rates;
        }

        return [];
    }

    public function syncRates(string $date, string $baseCurrency, array $symbols): int
    {
        $baseCurrency = strtoupper($baseCurrency);
        $symbols = array_values(array_unique(array_filter(array_map('strtoupper', $symbols))));

        $symbols = array_values(array_filter($symbols, fn ($symbol) => $symbol !== $baseCurrency));
        if (empty($symbols)) {
            return 0;
        }

        $rates = $this->fetchRates($date, $baseCurrency, $symbols);
        if (!$rates) {
            return 0;
        }

        $rateDate = $date === 'latest' ? now()->toDateString() : Carbon::parse($date)->toDateString();
        $rows = [];
        $now = now();

        foreach ($rates as $symbol => $rate) {
            $rows[] = [
                'rate_date' => $rateDate,
                'from_currency' => $baseCurrency,
                'to_currency' => strtoupper($symbol),
                'rate' => $rate,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ExchangeRate::upsert(
            $rows,
            ['rate_date', 'from_currency', 'to_currency'],
            ['rate', 'updated_at']
        );

        return count($rows);
    }
}
