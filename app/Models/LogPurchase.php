<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogPurchase extends Model
{
    use HasFactory;

    protected $fillable = ['doc_no', 'tab', 'name', 'note_reject'];
}
