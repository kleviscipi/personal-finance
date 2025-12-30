<?php

namespace App\Http\Controllers;

use App\Models\AccountSettings;
use App\Jobs\RecomputeAccountBaseAmounts;
use App\Services\CurrencyService;
use App\Support\ActiveAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function show(Request $request, CurrencyService $currencyService): Response|RedirectResponse
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $settings = $account->settings ?? AccountSettings::firstOrCreate([
            'account_id' => $account->id,
        ]);

        return Inertia::render('Settings', [
            'account' => $account,
            'settings' => $settings,
            'currencies' => $currencyService->getSupportedCurrencies(),
        ]);
    }

    public function update(Request $request, CurrencyService $currencyService): RedirectResponse
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $supportedCurrencies = array_keys($currencyService->getSupportedCurrencies());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'base_currency' => ['required', Rule::in($supportedCurrencies)],
            'locale' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:255'],
            'date_format' => ['required', 'string', 'max:20'],
            'time_format' => ['required', 'string', 'max:20'],
            'notifications_enabled' => ['nullable', 'boolean'],
        ]);

        $baseCurrencyChanged = $account->base_currency !== $validated['base_currency'];

        DB::transaction(function () use ($account, $validated) {
            $account->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'base_currency' => $validated['base_currency'],
            ]);

            $settings = $account->settings ?? AccountSettings::firstOrCreate([
                'account_id' => $account->id,
            ]);

            $settings->update([
                'locale' => $validated['locale'],
                'timezone' => $validated['timezone'],
                'date_format' => $validated['date_format'],
                'time_format' => $validated['time_format'],
                'notifications_enabled' => (bool) ($validated['notifications_enabled'] ?? false),
            ]);
        });

        if ($baseCurrencyChanged) {
            RecomputeAccountBaseAmounts::dispatch($account->id);
        }

        return redirect()
            ->route('settings')
            ->with('message', 'Settings updated successfully.');
    }
}
