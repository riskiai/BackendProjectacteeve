<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_type_id',
        'name',
        'address',
        'npwp',
        'pic_name',
        'phone',
        'email',
        'file',
        'bank_name',
        'branch',
        'account_name',
        'currency',
        'account_number',
        'swift_code',
    ];

    public function contactType()
    {
        return $this->belongsTo(ContactType::class);
    }
}
