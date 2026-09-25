<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZohoToken extends Model
{
    protected $fillable = [
        'authorized_by',
        'accounts_url',
        'api_domain',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'authorized_by' => 'integer',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'expires_at' => 'datetime',
    ];

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
