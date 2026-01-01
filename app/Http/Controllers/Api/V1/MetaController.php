<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\CurrencyService;
use Illuminate\Http\Request;

class MetaController extends ApiController
{
    public function __construct(
        private CurrencyService $currencyService
    ) {}

    public function currencies(Request $request)
    {
        $currencies = array_values($this->currencyService->getSupportedCurrencies());

        return response()->json([
            'data' => $currencies,
        ]);
    }
}
