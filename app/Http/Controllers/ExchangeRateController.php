<?php

namespace App\Http\Controllers;

use App\Models\AccountSettings;
use App\Models\ExchangeRate;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;
use App\Support\ActiveAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExchangeRateController extends Controller
{
    private function resolveSyncSettings(AccountSettings $settings, array $supported, string $fallbackBase): array
    {
        $preferences = $settings->preferences ?? [];
        $defaultBase = config('services.exchangerate_host.sync_base', $fallbackBase);
        $defaultSymbols = config('services.exchangerate_host.sync_symbols', '');

        $syncBase = strtoupper($preferences['fx_sync_base'] ?? $defaultBase ?? $fallbackBase);
        if (!in_array($syncBase, $supported, true)) {
            $syncBase = $fallbackBase;
        }

        $symbolsRaw = $preferences['fx_sync_symbols'] ?? $defaultSymbols;
        $symbols = array_filter(array_map('trim', explode(',', (string) $symbolsRaw)));
        $symbols = array_values(array_filter(
            array_map('strtoupper', $symbols),
            fn ($symbol) => $symbol !== $syncBase && in_array($symbol, $supported, true)
        ));

        if (!$symbols) {
            $symbols = array_values(array_filter($supported, fn ($symbol) => $symbol !== $syncBase));
        }

        return [
            'sync_base' => $syncBase,
            'sync_symbols' => $symbols,
            'sync_source' => isset($preferences['fx_sync_base']) || isset($preferences['fx_sync_symbols'])
                ? 'account'
                : 'env',
        ];
    }

    public function index(Request $request, CurrencyService $currencyService): Response|RedirectResponse
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $this->authorize('update', $account);

        $settings = $account->settings ?? AccountSettings::firstOrCreate([
            'account_id' => $account->id,
        ]);

        $supported = array_keys($currencyService->getSupportedCurrencies());
        $resolved = $this->resolveSyncSettings($settings, $supported, $account->base_currency);
        $syncBase = $resolved['sync_base'];
        $symbols = $resolved['sync_symbols'];
        $syncSource = $resolved['sync_source'];

        $rangeStart = now()->subDays(9)->toDateString();

        $rates = ExchangeRate::query()
            ->where('from_currency', $syncBase)
            ->whereIn('to_currency', $symbols)
            ->where('rate_date', '>=', $rangeStart)
            ->orderByDesc('rate_date')
            ->orderBy('to_currency')
            ->get(['from_currency', 'to_currency', 'rate', 'rate_date']);

        $latestDate = $rates->first()?->rate_date?->toDateString();

        return Inertia::render('ExchangeRates/Index', [
            'currentAccount' => $account,
            'baseCurrency' => $account->base_currency,
            'syncBase' => $syncBase,
            'symbols' => $symbols,
            'syncSource' => $syncSource,
            'supportedCurrencies' => $supported,
            'latestDate' => $latestDate,
            'rangeStart' => $rangeStart,
            'rates' => $rates->map(fn ($rate) => [
                'from_currency' => $rate->from_currency,
                'to_currency' => $rate->to_currency,
                'rate' => $rate->rate,
                'rate_date' => $rate->rate_date?->toDateString(),
            ]),
        ]);
    }

    public function sync(Request $request, CurrencyService $currencyService, ExchangeRateService $exchangeRateService): RedirectResponse
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $this->authorize('update', $account);

        $settings = $account->settings ?? AccountSettings::firstOrCreate([
            'account_id' => $account->id,
        ]);

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $date = $validated['date'] ?? now()->toDateString();
        $supported = array_keys($currencyService->getSupportedCurrencies());
        $resolved = $this->resolveSyncSettings($settings, $supported, $account->base_currency);
        $syncBase = $resolved['sync_base'];
        $symbols = $resolved['sync_symbols'];

        try {
            $count = $exchangeRateService->syncRates($date, $syncBase, $symbols);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to sync exchange rates: ' . $e->getMessage());
        }

        return back()->with('message', "Synced {$count} rates for {$syncBase} on {$date}.");
    }

    public function updateSettings(Request $request, CurrencyService $currencyService): RedirectResponse
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $this->authorize('update', $account);

        $supported = array_keys($currencyService->getSupportedCurrencies());

        $validated = $request->validate([
            'sync_base' => ['required', 'string', 'size:3', Rule::in($supported)],
            'sync_symbols' => ['nullable', 'string', 'max:255'],
        ]);

        $symbols = array_filter(array_map('trim', explode(',', $validated['sync_symbols'] ?? '')));
        $symbols = array_values(array_filter(
            array_map('strtoupper', $symbols),
            fn ($symbol) => $symbol !== $validated['sync_base'] && in_array($symbol, $supported, true)
        ));

        $settings = $account->settings ?? AccountSettings::firstOrCreate([
            'account_id' => $account->id,
        ]);

        $preferences = $settings->preferences ?? [];
        $preferences['fx_sync_base'] = strtoupper($validated['sync_base']);
        $preferences['fx_sync_symbols'] = $symbols ? implode(',', $symbols) : null;

        $settings->update([
            'preferences' => $preferences,
        ]);

        return back()->with('message', 'Exchange rate sync settings updated.');
    }
}
