<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'identification',
        'email',
        'password',
        'location',
        'phone',
        'about',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            UrlModel::class,
            'authority',
            'user_id',
            'linkName_id'
        );
    }

    public function playgrounds(): HasMany
    {
        return $this->hasMany(Playground::class);
    }

    public function modelAssets(): HasMany
    {
        return $this->hasMany(ModelAsset::class);
    }
}
