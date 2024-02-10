<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactType extends Model
{
    use HasFactory;

    const VENDOR = 1;
    const CLIENT = 3;

    protected $table = 'contact_type';

    protected $fillable = [
        'name',
    ];
}
