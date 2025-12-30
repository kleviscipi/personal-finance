<?php

use App\Models\Account;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('exchange-rates:sync {--date=} {--base=} {--symbols=} {--all-bases}', function () {
    $date = $this->option('date') ?? now()->toDateString();
    $baseOption = $this->option('base');
    $symbolsOption = $this->option('symbols');
    $allBases = (bool) $this->option('all-bases');

    $currencyService = app(CurrencyService::class);
    $exchangeRateService = app(ExchangeRateService::class);

    $supported = array_keys($currencyService->getSupportedCurrencies());
    $symbols = $symbolsOption
        ? array_filter(array_map('trim', explode(',', $symbolsOption)))
        : $supported;

    $bases = $allBases ? $supported : [($baseOption ?: 'USD')];
    $total = 0;

    foreach ($bases as $base) {
        $count = $exchangeRateService->syncRates($date, $base, $symbols);
        $total += $count;
        $this->info("Synced {$count} rates for base {$base} on {$date}.");
    }

    $this->comment("Total rates synced: {$total}.");
})->purpose('Sync exchange rates from exchangerate.host');

Artisan::command('exchange-rates:sync-accounts {--date=}', function () {
    $date = $this->option('date') ?? now()->toDateString();
    $currencyService = app(CurrencyService::class);
    $exchangeRateService = app(ExchangeRateService::class);
    $supported = array_keys($currencyService->getSupportedCurrencies());

    $defaultBase = config('services.exchangerate_host.sync_base', 'ALL');
    $defaultSymbolsRaw = config('services.exchangerate_host.sync_symbols', 'USD,EUR');
    $defaultSymbols = array_filter(array_map('trim', explode(',', $defaultSymbolsRaw)));

    $total = 0;
    $accounts = Account::query()->where('is_active', true)->get();

    foreach ($accounts as $account) {
        $settings = $account->settings()->first();
        $preferences = $settings?->preferences ?? [];
        $syncBase = strtoupper($preferences['fx_sync_base'] ?? $defaultBase ?? $account->base_currency);
        if (!in_array($syncBase, $supported, true)) {
            $syncBase = $account->base_currency;
        }

        $symbolsRaw = $preferences['fx_sync_symbols'] ?? $defaultSymbols;
        $symbolsList = is_array($symbolsRaw)
            ? $symbolsRaw
            : array_filter(array_map('trim', explode(',', (string) $symbolsRaw)));
        $symbols = array_values(array_filter(
            array_map('strtoupper', $symbolsList),
            fn ($symbol) => $symbol !== $syncBase && in_array($symbol, $supported, true)
        ));

        if (!$symbols) {
            $symbols = array_values(array_filter($supported, fn ($symbol) => $symbol !== $syncBase));
        }

        if (!$symbols) {
            continue;
        }

        $count = $exchangeRateService->syncRates($date, $syncBase, $symbols);
        $total += $count;
        $this->info("Synced {$count} rates for account {$account->id} ({$syncBase}) on {$date}.");
    }

    $this->comment("Total rates synced: {$total}.");
})->purpose('Sync exchange rates for all accounts using account settings');

$syncTime = config('services.exchangerate_host.sync_time', '02:00');

Schedule::command('exchange-rates:sync-accounts')
    ->dailyAt($syncTime)
    ->withoutOverlapping();
