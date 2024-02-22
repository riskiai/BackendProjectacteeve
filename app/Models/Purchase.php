<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Purchase extends Model
{
    use HasFactory;

    const ATTACHMENT_FILE = 'attachment/purchase';

    const TAB_SUBMIT = 1;
    const TAB_VERIFIED = 2;
    const TAB_PAYMENT_REQUEST = 3;
    const TAB_PAID = 4;

    const TEXT_EVENT = "Event Purchase";
    const TEXT_OPERATIONAL = "Operational Purchase";

    const TYPE_EVENT = 1;
    const TYPE_OPERATIONAL = 2;

    protected $fillable = [
        'doc_no',
        'doc_type',
        'tab',
        'purchase_id',
        'purchase_category_id',
        'company_id',
        'project_id',
        'purchase_status_id',
        'description',
        'remarks',
        'sub_total',
        'ppn',
        'pph',
        'total',
        'file',
        'date',
        'due_date',
    ];

    public function purchaseCategory(): HasOne
    {
        return $this->hasOne(PurchaseCategory::class, 'id', 'purchase_category_id');
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class, 'id', 'project_id');
    }

    public function purchaseStatus(): HasOne
    {
        return $this->hasOne(PurchaseStatus::class, 'id', 'purchase_status_id');
    }

    public function taxPpn(): HasOne
    {
        return $this->hasOne(Tax::class, 'id', 'ppn');
    }

    public function taxPph(): HasOne
    {
        return $this->hasOne(Tax::class, 'id', 'pph');
    }
}
