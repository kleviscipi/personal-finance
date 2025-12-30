<?php

namespace App\Http\Middleware;

use App\Services\CurrencyService;
use App\Support\ActiveAccount;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'accounts' => fn () => $request->user()
                ? $request->user()->accounts()->orderBy('name')->get(['accounts.id', 'accounts.name', 'accounts.base_currency'])
                : [],
            'activeAccount' => fn () => ActiveAccount::resolve($request),
            'currencies' => fn () => app(CurrencyService::class)->getSupportedCurrencies(),
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
