<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'created_by',
        'type',
        'amount',
        'currency',
        'next_run_date',
        'end_date',
        'frequency',
        'interval',
        'anchor_day',
        'anchor_month',
        'category_id',
        'subcategory_id',
        'description',
        'payment_method',
        'is_active',
        'last_generated_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'next_run_date' => 'date',
        'end_date' => 'date',
        'interval' => 'integer',
        'anchor_day' => 'integer',
        'anchor_month' => 'integer',
        'is_active' => 'boolean',
        'last_generated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'recurring_transaction_tag')->withTimestamps();
    }
}
