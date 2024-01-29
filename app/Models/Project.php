<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    protected $table = 'projects';

    protected $fillable = [
        'name',
        'company_name',
        'billing',
        'cost_estimate',
        'margin',
        'percent',
        'file',
    ];
}
