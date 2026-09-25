<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaygroundObject extends Model
{
    protected $fillable = [
        'playground_id',
        'model_asset_id',
        'object_uuid',
        'name',
        'position_x',
        'position_y',
        'position_z',
        'rotation_x',
        'rotation_y',
        'rotation_z',
        'scale_x',
        'scale_y',
        'scale_z',
        'visible',
        'locked',
        'sort_order',
    ];

    protected $casts = [
        'playground_id' => 'integer',
        'model_asset_id' => 'integer',
        'position_x' => 'float',
        'position_y' => 'float',
        'position_z' => 'float',
        'rotation_x' => 'float',
        'rotation_y' => 'float',
        'rotation_z' => 'float',
        'scale_x' => 'float',
        'scale_y' => 'float',
        'scale_z' => 'float',
        'visible' => 'boolean',
        'locked' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function playground(): BelongsTo
    {
        return $this->belongsTo(Playground::class);
    }

    public function modelAsset(): BelongsTo
    {
        return $this->belongsTo(ModelAsset::class);
    }
}
