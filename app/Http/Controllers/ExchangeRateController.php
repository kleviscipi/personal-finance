<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExchangeRateController extends Controller
{
    public function __construct(
        private CurrencyService $currencyService
    ) {}

    /**
     * Display a listing of exchange rates.
     */
    public function index(Request $request)
    {
        $query = ExchangeRate::query();

        if ($request->filled('base_currency')) {
            $query->where('base_currency', $request->base_currency);
        }

        if ($request->filled('target_currency')) {
            $query->where('target_currency', $request->target_currency);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $exchangeRates = $query->orderBy('date', 'desc')->paginate(20);

        return Inertia::render('ExchangeRates/Index', [
            'exchangeRates' => $exchangeRates,
            'currencies' => $this->currencyService->getSupportedCurrencies(),
            'filters' => $request->only(['base_currency', 'target_currency', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Store a newly created exchange rate.
     */
    public function store(Request $request)
    {
        $supportedCurrencies = implode(',', $this->currencyService->getSupportedCurrencyCodes());

        $validated = $request->validate([
            'base_currency' => 'required|in:' . $supportedCurrencies,
            'target_currency' => 'required|in:' . $supportedCurrencies . '|different:base_currency',
            'rate' => 'required|numeric|min:0.00000001',
            'date' => 'required|date',
            'source' => 'nullable|string|max:255',
        ]);

        $this->currencyService->storeExchangeRate(
            $validated['base_currency'],
            $validated['target_currency'],
            (string) $validated['rate'],
            $validated['date'],
            $validated['source'] ?? 'manual'
        );

        return redirect()->back()->with('message', 'Exchange rate saved successfully.');
    }

    /**
     * Update the specified exchange rate.
     */
    public function update(Request $request, ExchangeRate $exchangeRate)
    {
        $validated = $request->validate([
            'rate' => 'required|numeric|min:0.00000001',
            'source' => 'nullable|string|max:255',
        ]);

        $exchangeRate->update([
            'rate' => $validated['rate'],
            'source' => $validated['source'] ?? $exchangeRate->source,
        ]);

        return redirect()->back()->with('message', 'Exchange rate updated successfully.');
    }

    /**
     * Remove the specified exchange rate.
     */
    public function destroy(ExchangeRate $exchangeRate)
    {
        $exchangeRate->delete();

        return redirect()->back()->with('message', 'Exchange rate deleted successfully.');
    }
}
