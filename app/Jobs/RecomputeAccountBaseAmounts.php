<?php

namespace App\Jobs;

use App\Models\Account;
use App\Services\CurrencyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecomputeAccountBaseAmounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $accountId
    ) {}

    /**
     * Execute the job.
     * 
     * This job is dispatched when an account's base currency changes.
     * It could be used to recompute all transaction amounts in the new base currency,
     * update cached balances, or trigger analytics recalculation.
     */
    public function handle(CurrencyService $currencyService): void
    {
        $account = Account::find($this->accountId);
        
        if (!$account) {
            Log::warning("Account {$this->accountId} not found for base amount recomputation");
            return;
        }

        Log::info("Recomputing base amounts for account {$account->id} with base currency {$account->base_currency}");
        
        // Future implementation:
        // 1. Convert all transactions to base currency using exchange rates
        // 2. Update cached balance fields if they exist
        // 3. Invalidate analytics cache
        // 4. Notify users of the completed conversion
        
        // For now, this is a placeholder that logs the event
        // The actual conversion happens on-the-fly in the CurrencyService
    }
}
