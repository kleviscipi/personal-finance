<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\TransactionResource;
use App\Models\Subcategory;
use App\Models\Transaction;
use App\Services\CurrencyService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransactionController extends ApiController
{
    public function __construct(
        private TransactionService $transactionService,
        private CurrencyService $currencyService
    ) {}

    public function index(Request $request)
    {
        $account = $this->resolveAccount($request);

        $query = Transaction::where('account_id', $account->id)
            ->with(['category', 'subcategory', 'creator', 'tags']);

        if ($request->filled('q')) {
            $search = mb_strtolower($request->input('q'));
            $like = '%' . $search . '%';
            $query->where(function ($query) use ($like) {
                $query->whereRaw('LOWER(description) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(payment_method) LIKE ?', [$like])
                    ->orWhereHas('category', function ($query) use ($like) {
                        $query->whereRaw('LOWER(name) LIKE ?', [$like]);
                    })
                    ->orWhereHas('subcategory', function ($query) use ($like) {
                        $query->whereRaw('LOWER(name) LIKE ?', [$like]);
                    })
                    ->orWhereHas('creator', function ($query) use ($like) {
                        $query->whereRaw('LOWER(name) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                    });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->input('subcategory_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }

        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->input('amount_min'));
        }

        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', $request->input('amount_max'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->input('created_by'));
        }

        $tagIds = array_filter((array) $request->input('tag_ids', []), static fn ($id) => $id !== null && $id !== '');
        if (!empty($tagIds)) {
            $query->whereHas('tags', function ($query) use ($tagIds) {
                $query->whereIn('tags.id', $tagIds);
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $transactions = $query->latest('date')->paginate($perPage);

        return TransactionResource::collection($transactions);
    }

    public function show(Request $request, int $transactionId)
    {
        $account = $this->resolveAccount($request);

        $transaction = Transaction::where('account_id', $account->id)
            ->with(['category', 'subcategory', 'creator', 'tags'])
            ->findOrFail($transactionId);

        return new TransactionResource($transaction);
    }

    public function store(Request $request)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin', 'member']);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['expense', 'income', 'transfer'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(array_keys($this->currencyService->getSupportedCurrencies()))],
            'date' => ['required', 'date'],
            'category_id' => [
                Rule::requiredIf(fn () => $request->filled('subcategory_id')),
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('account_id', $account->id)),
            ],
            'subcategory_id' => ['nullable', Rule::exists('subcategories', 'id')],
            'description' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'tag_ids' => ['array'],
            'tag_ids.*' => [
                Rule::exists('tags', 'id')->where(fn ($query) => $query->where('account_id', $account->id)),
            ],
            'tag_names' => ['nullable'],
        ]);

        $validated = $this->normalizeSubcategory($validated, $account->id);

        $validated['tag_ids'] = $validated['tag_ids'] ?? [];
        $validated['tag_names'] = $this->parseTagNames($request->input('tag_names'));

        $transaction = $this->transactionService->createTransaction($account, $request->user(), $validated);

        return (new TransactionResource($transaction->load(['category', 'subcategory', 'creator', 'tags'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, int $transactionId)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin', 'member']);

        $transaction = Transaction::where('account_id', $account->id)->findOrFail($transactionId);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['expense', 'income', 'transfer'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(array_keys($this->currencyService->getSupportedCurrencies()))],
            'date' => ['required', 'date'],
            'category_id' => [
                Rule::requiredIf(fn () => $request->filled('subcategory_id')),
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('account_id', $account->id)),
            ],
            'subcategory_id' => ['nullable', Rule::exists('subcategories', 'id')],
            'description' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'tag_ids' => ['array'],
            'tag_ids.*' => [
                Rule::exists('tags', 'id')->where(fn ($query) => $query->where('account_id', $account->id)),
            ],
            'tag_names' => ['nullable'],
        ]);

        $validated = $this->normalizeSubcategory($validated, $account->id);

        $validated['tag_ids'] = $validated['tag_ids'] ?? [];
        $validated['tag_names'] = $this->parseTagNames($request->input('tag_names'));

        $transaction = $this->transactionService->updateTransaction($transaction, $request->user(), $validated);

        return new TransactionResource($transaction->load(['category', 'subcategory', 'creator', 'tags']));
    }

    public function destroy(Request $request, int $transactionId)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin', 'member']);

        $transaction = Transaction::where('account_id', $account->id)->findOrFail($transactionId);

        $this->transactionService->deleteTransaction($transaction, $request->user());

        return response()->json([], 204);
    }

    private function parseTagNames($value): array
    {
        if (is_array($value)) {
            $names = $value;
        } else {
            $names = preg_split('/,/', (string) $value) ?: [];
        }

        $names = array_map(static fn ($name) => trim((string) $name), $names);
        $names = array_filter($names, static fn ($name) => $name !== '');

        return array_slice(array_values(array_unique($names)), 0, 20);
    }

    private function normalizeSubcategory(array $validated, int $accountId): array
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
}
