<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\AccountSettings;
use App\Services\CurrencyService;
use App\Services\TransactionService;
use App\Support\ActiveAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function create(CurrencyService $currencyService): Response
    {
        return Inertia::render('Accounts/Create', [
            'currencies' => $currencyService->getSupportedCurrencies(),
        ]);
    }

    public function store(
        Request $request,
        CurrencyService $currencyService,
        TransactionService $transactionService
    ): RedirectResponse
    {
        $supportedCurrencies = array_keys($currencyService->getSupportedCurrencies());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'base_currency' => ['required', Rule::in($supportedCurrencies)],
            'description' => ['nullable', 'string', 'max:1000'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'opening_balance_date' => ['nullable', 'date'],
        ]);

        $account = Account::create([
            'name' => $validated['name'],
            'base_currency' => $validated['base_currency'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        AccountSettings::create([
            'account_id' => $account->id,
        ]);

        $request->user()->accounts()->attach($account->id, [
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        ActiveAccount::store($request, $account);

        if (!empty($validated['opening_balance'])) {
            $openingCategory = Category::firstOrCreate(
                [
                    'account_id' => $account->id,
                    'name' => 'Opening Balance',
                ],
                [
                    'type' => 'income',
                    'icon' => 'OB',
                    'color' => '#64748b',
                    'is_system' => true,
                    'order' => 0,
                ]
            );

            $transactionService->createTransaction($account, $request->user(), [
                'type' => 'income',
                'amount' => $validated['opening_balance'],
                'currency' => $validated['base_currency'],
                'date' => $validated['opening_balance_date'] ?? now()->toDateString(),
                'category_id' => $openingCategory->id,
                'description' => 'Opening balance',
                'payment_method' => 'opening_balance',
                'metadata' => ['opening_balance' => true],
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('message', 'Account created successfully.');
    }

    public function setActive(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'integer'],
        ]);

        $account = $request->user()
            ->accounts()
            ->where('accounts.id', $validated['account_id'])
            ->firstOrFail();

        ActiveAccount::store($request, $account);

        return back();
    }
}
