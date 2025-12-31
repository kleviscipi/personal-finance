<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'created_by',
        'type',
        'amount',
        'currency',
        'date',
        'category_id',
        'subcategory_id',
        'description',
        'payment_method',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'date' => 'date',
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

    public function histories(): HasMany
    {
        return $this->hasMany(TransactionHistory::class);
    }

    public function latestHistory(): HasOne
    {
        return $this->hasOne(TransactionHistory::class)->latestOfMany();
    }
}
