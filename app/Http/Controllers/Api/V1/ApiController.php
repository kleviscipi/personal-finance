<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    protected function resolveAccount(Request $request, ?int $accountId = null): Account
    {
        $user = $request->user();
        $accountId = $accountId ?? $this->accountIdFromRequest($request);

        $query = $user->accounts();
        if ($accountId) {
            $query->where('accounts.id', $accountId);
        }

        $account = $query->first();
        if (!$account) {
            abort(404, 'Account not found.');
        }

        if (!$account->pivot?->is_active) {
            abort(403, 'Account is inactive.');
        }

        return $account;
    }

    protected function accountIdFromRequest(Request $request): ?int
    {
        $accountId = $request->input('account_id')
            ?? $request->query('account_id')
            ?? $request->header('X-Account-Id');

        if ($accountId === null || $accountId === '') {
            return null;
        }

        return (int) $accountId;
    }

    protected function ensureAccountRole(Request $request, Account $account, array $roles): void
    {
        $membership = $request->user()
            ->accounts()
            ->where('accounts.id', $account->id)
            ->first();

        $pivot = $membership?->pivot;

        if (!$pivot || !$pivot->is_active || !in_array($pivot->role, $roles, true)) {
            abort(403);
        }
    }
}
