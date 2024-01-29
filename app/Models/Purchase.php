<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_category_id',
        'purchase_status_id',
        'project',
        'doc_no',
        'description',
        'remarks',
        'subtotal',
        'ppn',
        'total',
        'file',
        'date',
        'due_date',
        'created_by',
    ];

    public function category()
    {
        return $this->belongsTo(PurchaseCategory::class, 'purchase_category_id');
    }

    public function status()
    {
        return $this->belongsTo(PurchaseStatus::class, 'purchase_status_id');
    }
}
