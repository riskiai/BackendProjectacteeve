<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;

    /* Disini Dirubah Untuk + - */
    const TAX_PPN = "ppn";
    const TAX_PPH = "pph";

    protected $table = 'taxs';

    protected $fillable = [
        'name',
        'description',
        'percent',
        'type',
    ];
}
