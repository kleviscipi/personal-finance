<?php

namespace App\Services;

use App\Models\Account;
use App\Models\RecurringTransaction;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecurringTransactionService
{
    public function __construct(private TransactionService $transactionService) {}

    public function createRecurringTransaction(Account $account, User $user, array $data): RecurringTransaction
    {
        return DB::transaction(function () use ($account, $user, $data) {
            $attributes = $this->prepareAttributes($data);

            $recurringTransaction = RecurringTransaction::create([
                ...$attributes,
                'account_id' => $account->id,
                'created_by' => $user->id,
            ]);

            $this->syncTags($recurringTransaction, $account, $data);

            return $recurringTransaction->fresh(['category', 'subcategory', 'creator', 'tags']);
        });
    }

    public function updateRecurringTransaction(RecurringTransaction $recurringTransaction, array $data): RecurringTransaction
    {
        return DB::transaction(function () use ($recurringTransaction, $data) {
            $recurringTransaction->update($this->prepareAttributes($data));
            $this->syncTags($recurringTransaction, $recurringTransaction->account, $data);

            return $recurringTransaction->fresh(['category', 'subcategory', 'creator', 'tags']);
        });
    }

    public function deleteRecurringTransaction(RecurringTransaction $recurringTransaction): bool
    {
        return $recurringTransaction->delete();
    }

    public function runDueTransactions(Carbon|string|null $asOf = null, ?Account $account = null): array
    {
        $asOfDate = $asOf instanceof Carbon
            ? $asOf->copy()->startOfDay()
            : Carbon::parse($asOf ?: now())->startOfDay();

        $query = RecurringTransaction::query()
            ->where('is_active', true)
            ->whereDate('next_run_date', '<=', $asOfDate->toDateString())
            ->with(['account', 'creator', 'tags'])
            ->orderBy('next_run_date')
            ->orderBy('id');

        if ($account) {
            $query->where('account_id', $account->id);
        }

        $templatesProcessed = 0;
        $transactionsCreated = 0;

        foreach ($query->get() as $template) {
            [$processed, $created] = $this->processTemplate($template, $asOfDate);
            $templatesProcessed += $processed ? 1 : 0;
            $transactionsCreated += $created;
        }

        return [
            'templates_processed' => $templatesProcessed,
            'transactions_created' => $transactionsCreated,
            'as_of' => $asOfDate->toDateString(),
        ];
    }

    public function validateSubcategory(array $validated, int $accountId): array
    {
        if (empty($validated['subcategory_id'])) {
            return $validated;
        }

        $subcategory = Subcategory::where('id', $validated['subcategory_id'])
            ->whereHas('category', function ($query) use ($accountId) {
                $query->where('account_id', $accountId);
            })
            ->first();

        if (!$subcategory) {
            throw ValidationException::withMessages([
                'subcategory_id' => ['Invalid subcategory for this account.'],
            ]);
        }

        if (!empty($validated['category_id']) && (int) $validated['category_id'] !== $subcategory->category_id) {
            throw ValidationException::withMessages([
                'subcategory_id' => ['Subcategory does not belong to the selected category.'],
            ]);
        }

        $validated['category_id'] = $validated['category_id'] ?? $subcategory->category_id;

        return $validated;
    }

    private function processTemplate(RecurringTransaction $template, Carbon $asOfDate): array
    {
        return DB::transaction(function () use ($template, $asOfDate) {
            /** @var RecurringTransaction|null $locked */
            $locked = RecurringTransaction::query()
                ->whereKey($template->id)
                ->lockForUpdate()
                ->with(['account', 'creator', 'tags'])
                ->first();

            if (!$locked || !$locked->is_active || $locked->next_run_date->gt($asOfDate)) {
                return [false, 0];
            }

            $created = 0;
            $runDate = $locked->next_run_date->copy();
            $endDate = $locked->end_date?->copy();

            while ($runDate->lte($asOfDate) && (!$endDate || $runDate->lte($endDate))) {
                $this->transactionService->createTransaction(
                    $locked->account,
                    $locked->creator,
                    [
                        'type' => $locked->type,
                        'amount' => $locked->amount,
                        'currency' => $locked->currency,
                        'date' => $runDate->toDateString(),
                        'category_id' => $locked->category_id,
                        'subcategory_id' => $locked->subcategory_id,
                        'description' => $locked->description,
                        'payment_method' => $locked->payment_method,
                        'tag_ids' => $locked->tags->pluck('id')->all(),
                        'metadata' => array_filter([
                            'recurring_transaction_id' => $locked->id,
                            'generated_from_schedule' => true,
                        ]),
                    ]
                );

                $created++;
                $locked->last_generated_at = now();
                $runDate = $this->advanceDate($runDate, $locked);
            }

            $locked->next_run_date = $runDate->toDateString();

            if ($endDate && $runDate->gt($endDate)) {
                $locked->is_active = false;
            }

            $locked->save();

            return [true, $created];
        });
    }

    private function prepareAttributes(array $data): array
    {
        $nextRunDate = Carbon::parse($data['next_run_date'])->startOfDay();

        return [
            'type' => $data['type'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'next_run_date' => $nextRunDate->toDateString(),
            'end_date' => $data['end_date'] ?? null,
            'frequency' => $data['frequency'],
            'interval' => max(1, (int) ($data['interval'] ?? 1)),
            'anchor_day' => in_array($data['frequency'], ['monthly', 'yearly'], true) ? $nextRunDate->day : null,
            'anchor_month' => $data['frequency'] === 'yearly' ? $nextRunDate->month : null,
            'category_id' => $data['category_id'] ?? null,
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'description' => $data['description'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }

    private function advanceDate(Carbon $current, RecurringTransaction $template): Carbon
    {
        $interval = max(1, (int) $template->interval);

        return match ($template->frequency) {
            'daily' => $current->copy()->addDays($interval),
            'weekly' => $current->copy()->addWeeks($interval),
            'monthly' => $this->advanceMonthly($current, $template->anchor_day, $interval),
            'yearly' => $this->advanceYearly($current, $template->anchor_month, $template->anchor_day, $interval),
            default => $current->copy()->addMonth(),
        };
    }

    private function advanceMonthly(Carbon $current, ?int $anchorDay, int $interval): Carbon
    {
        $candidate = $current->copy()->addMonthsNoOverflow($interval)->startOfDay();
        $targetDay = min($anchorDay ?: $candidate->day, $candidate->daysInMonth);

        return $candidate->day($targetDay);
    }

    private function advanceYearly(Carbon $current, ?int $anchorMonth, ?int $anchorDay, int $interval): Carbon
    {
        $candidate = $current->copy()->addYears($interval)->startOfDay();
        $targetMonth = $anchorMonth ?: $candidate->month;
        $candidate->month($targetMonth)->startOfMonth();
        $targetDay = min($anchorDay ?: $candidate->day, $candidate->daysInMonth);

        return $candidate->day($targetDay);
    }

    private function syncTags(RecurringTransaction $recurringTransaction, Account $account, array $data): void
    {
        if (!array_key_exists('tag_ids', $data) && !array_key_exists('tag_names', $data)) {
            return;
        }

        $tagIds = array_filter($data['tag_ids'] ?? [], static fn ($id) => $id !== null && $id !== '');
        $tagIds = array_map('intval', $tagIds);

        $tagNames = $data['tag_names'] ?? [];
        if (!is_array($tagNames)) {
            $tagNames = [];
        }

        $normalizedNames = collect($tagNames)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();

        if ($normalizedNames->isNotEmpty()) {
            $existingTags = Tag::where('account_id', $account->id)
                ->whereIn('name', $normalizedNames)
                ->get()
                ->keyBy('name');

            foreach ($normalizedNames as $name) {
                $tag = $existingTags->get($name);
                if (!$tag) {
                    $tag = Tag::create([
                        'account_id' => $account->id,
                        'name' => $name,
                    ]);
                }
                $tagIds[] = $tag->id;
            }
        }

        $recurringTransaction->tags()->sync(array_values(array_unique($tagIds)));
    }
}
