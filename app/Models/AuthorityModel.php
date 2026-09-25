<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthorityModel extends Model
{
    use HasFactory;

    protected $table = 'authority';
    protected $primaryKey = 'id';

    public $timestamps = false;
    protected $fillable = [
        'location',
        'linkName_id',
        'user_id',
    ];
}
