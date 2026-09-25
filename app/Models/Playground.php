<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Playground extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'qr_token',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'qr_token';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
