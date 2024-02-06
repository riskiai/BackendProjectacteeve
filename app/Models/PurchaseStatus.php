<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseStatus extends Model
{
    use HasFactory;

    const AWAITING = 1;
    const VERIFIED = 2;
    const OPEN = 3;
    const OVERDUE = 4;
    const DUEDATE = 5;
    const REJECTED = 6;
    const PAID = 7;

    protected $table = 'purchase_status';

    protected $fillable = [
        'name',
    ];
}
