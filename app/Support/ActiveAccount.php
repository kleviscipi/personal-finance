<?php

namespace App\Support;

use App\Models\Account;
use Illuminate\Http\Request;

class ActiveAccount
{
    public static function resolve(Request $request): ?Account
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        $activeId = $request->session()->get('active_account_id');

        $account = null;
        if ($activeId) {
            $account = $user->accounts()->where('accounts.id', $activeId)->first();
        }

        if (!$account) {
            $account = $user->accounts()->orderBy('accounts.id')->first();
        }

        if ($account && $account->id !== $activeId) {
            $request->session()->put('active_account_id', $account->id);
        }

        return $account;
    }

    public static function store(Request $request, Account $account): void
    {
        $request->session()->put('active_account_id', $account->id);
    }
}
