<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    const ADMIN = 1;
    const USER = 2;
    const ADMIN_MASTER = 3;

    protected $table = 'roles';

    protected $fillable = [
        'name',
    ];
}
