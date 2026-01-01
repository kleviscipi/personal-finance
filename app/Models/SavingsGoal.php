<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavingsGoal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'user_id',
        'category_id',
        'subcategory_id',
        'name',
        'target_amount',
        'initial_amount',
        'currency',
        'tracking_mode',
        'start_date',
        'target_date',
        'settings',
    ];

    protected $casts = [
        'target_amount' => 'decimal:4',
        'initial_amount' => 'decimal:4',
        'start_date' => 'date',
        'target_date' => 'date',
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
