<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'user_id',
        'category_id',
        'subcategory_id',
        'amount',
        'currency',
        'period',
        'start_date',
        'end_date',
        'settings',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'start_date' => 'date',
        'end_date' => 'date',
        'settings' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }
}
