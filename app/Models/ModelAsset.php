<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelAsset extends Model
{
    protected $fillable = [
        'user_id',
        'playground_id',
        'name',
        'file_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'thumbnail_path',
        'active',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'playground_id' => 'integer',
        'file_size' => 'integer',
        'active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function playground(): BelongsTo
    {
        return $this->belongsTo(Playground::class);
    }

    public function playgroundObjects(): HasMany
    {
        return $this->hasMany(PlaygroundObject::class);
    }
}
