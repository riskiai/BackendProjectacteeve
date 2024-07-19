<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Purchase extends Model
{
    use HasFactory;

    protected $primaryKey = 'doc_no'; // Set doc_no as the primary key
    public $incrementing = false; // Indicate that the primary key is not auto-incrementing
    protected $keyType = 'string'; // Indicate that the primary key is of string type

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
        'date',
        'due_date',
        'reject_note',
        'user_id',
        // 'updated_at',
    ];

    protected $dates = ['created_at', 'updated_at'];

    // public $timestamps = true;
    
    public function taxPph(): HasOne
    {
        return $this->hasOne(Tax::class, 'id', 'pph');
    }

    // public function getTotalAttribute()
    // {
    //     $total = floatval($this->attributes['sub_total']); 

    //     // Ubah nilai ppn yang menggunakan koma menjadi titik
    //     $ppn = str_replace(',', '.', $this->attributes['ppn']);

    //     if (is_numeric($ppn)) {
    //         $ppn = floatval($ppn);
    //         $ppnAmount = ($total * $ppn) / 100; 
    //         $total += $ppnAmount;
    //     }

    //     // Ubah nilai pph yang menggunakan koma menjadi titik
    //     $pph = str_replace(',', '.', $this->attributes['pph']);

    //     if (is_numeric($pph)) {
    //         $pph = floatval($pph);
    //         $pphAmount = ($total * $pph) / 100; 
    //         $total -= $pphAmount;
    //     }

    //     return round($total);
    // }

    /* Menghitung Total Kesuluruhan */
    public function getTotalAttribute()
    {
        $total = $this->attributes['sub_total'];

        if ($this->attributes['ppn']) {
            $ppn = ($this->attributes['sub_total'] * $this->attributes['ppn']) / 100;
            $total += $ppn;
        }

       
        if ($this->attributes['pph']) {

            $pph = ($this->attributes['sub_total'] * $this->taxPph->percent) / 100;
            $total -= $pph;
        }
        
        return round($total);
    }

    // public function getTotalAttribute()
    // {
    // $total = 0;

    // if ($this->attributes['ppn']) {
    //     $ppn = ($this->attributes['sub_total'] * $this->attributes['ppn']) / 100;
    //     $total = $this->attributes['sub_total'] + $ppn;
    // }

    // if ($this->attributes['pph']) {
    //     $pph = ($total * $this->taxPph->first()->percent) / 100;
    //     $total -= $pph;
    // }

    // return $total;
    // }


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

    public function documents()
    {
        return $this->hasMany(Document::class, 'doc_no', 'doc_no');
    }

    public function document()
    {
        return $this->morphOne(Document::class, 'documentable');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LogPurchase::class, 'doc_no', 'doc_no');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
