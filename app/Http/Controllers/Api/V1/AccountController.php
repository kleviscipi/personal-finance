<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\AccountSettings;
use App\Models\Category;
use App\Services\CurrencyService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends ApiController
{
    public function __construct(
        private CurrencyService $currencyService,
        private TransactionService $transactionService
    ) {}

    public function index(Request $request)
    {
        $accounts = $request->user()->accounts()->get();

        return AccountResource::collection($accounts);
    }

    public function show(Request $request, int $accountId)
    {
        $account = $this->resolveAccount($request, $accountId);

        return new AccountResource($account);
    }

    public function store(Request $request)
    {
        $supportedCurrencies = array_keys($this->currencyService->getSupportedCurrencies());

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

            $this->transactionService->createTransaction($account, $request->user(), [
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

        $account = $request->user()->accounts()->where('accounts.id', $account->id)->first();

        return (new AccountResource($account))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $accountId)
    {
        $account = $this->resolveAccount($request, $accountId);
        $this->ensureAccountRole($request, $account, ['owner', 'admin']);

        $supportedCurrencies = array_keys($this->currencyService->getSupportedCurrencies());

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'base_currency' => ['sometimes', 'required', Rule::in($supportedCurrencies)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $account->update($validated);

        return new AccountResource($account->fresh());
    }

    public function destroy(Request $request, int $accountId)
    {
        $account = $this->resolveAccount($request, $accountId);
        $this->ensureAccountRole($request, $account, ['owner']);

        $account->delete();

        return response()->json([], 204);
    }
}
