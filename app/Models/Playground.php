<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Playground extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'qr_token',
        'qr_url',
        'mind_target_path',
        'mind_target_version',
        'target_compiled_at',
        'scene_saved_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'mind_target_version' => 'integer',
        'target_compiled_at' => 'datetime',
        'scene_saved_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'qr_token';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function objects(): HasMany
    {
        return $this->hasMany(PlaygroundObject::class);
    }
}
