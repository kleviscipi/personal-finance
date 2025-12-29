<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountSettings extends Model
{
    protected $fillable = [
        'account_id',
        'locale',
        'timezone',
        'date_format',
        'time_format',
        'notifications_enabled',
        'preferences',
    ];

    protected $casts = [
        'notifications_enabled' => 'boolean',
        'preferences' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
