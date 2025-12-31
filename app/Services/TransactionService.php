<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    private function applyOpeningBalanceMetadata(?Transaction $transaction, array $data): array
    {
        if (empty($data['category_id'])) {
            return $data;
        }

        $category = Category::find($data['category_id']);
        if (!$category) {
            return $data;
        }

        $isOpeningBalance = $category->is_system && $category->name === 'Opening Balance';
        $metadata = $data['metadata'] ?? ($transaction?->metadata ?? null);

        if ($isOpeningBalance) {
            $metadata = is_array($metadata) ? $metadata : [];
            $metadata['opening_balance'] = true;
        } elseif (is_array($metadata) && array_key_exists('opening_balance', $metadata)) {
            unset($metadata['opening_balance']);
        }

        if (is_array($metadata) && empty($metadata)) {
            $metadata = null;
        }

        $data['metadata'] = $metadata;

        return $data;
    }

    public function createTransaction(Account $account, User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($account, $user, $data) {
            $data = $this->applyOpeningBalanceMetadata(null, $data);

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'created_by' => $user->id,
                'type' => $data['type'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? $account->base_currency,
                'date' => $data['date'],
                'category_id' => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'description' => $data['description'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $this->createHistory($transaction, $user, 'created', null, $transaction->toArray());

            return $transaction;
        });
    }

    public function updateTransaction(Transaction $transaction, User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $user, $data) {
            $oldValues = $transaction->toArray();

            $data = $this->applyOpeningBalanceMetadata($transaction, $data);
            $transaction->update($data);
            
            $this->createHistory($transaction, $user, 'updated', $oldValues, $transaction->toArray());

            return $transaction->fresh();
        });
    }

    public function deleteTransaction(Transaction $transaction, User $user): bool
    {
        return DB::transaction(function () use ($transaction, $user) {
            $this->createHistory($transaction, $user, 'deleted', $transaction->toArray(), null);
            
            return $transaction->delete();
        });
    }

    private function createHistory(Transaction $transaction, User $user, string $action, ?array $oldValues, ?array $newValues): void
    {
        TransactionHistory::create([
            'transaction_id' => $transaction->id,
            'changed_by' => $user->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
