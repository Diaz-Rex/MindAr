<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UrlModel extends Model
{
    use HasFactory;
    protected $table = 'table_urls';
    protected $primaryKey = 'id';

    public $timestamps = false;
    protected $fillable = [
        'linkName',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'authority',
            'linkName_id',
            'user_id'
        );
    }
}
