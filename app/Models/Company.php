<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    /**
     * variable global yang bisa digunakan dimana aja
     * berfungsi jika path salah, tidak perlu mengganti semuanya
     * hanya perlu mengganti variable globalnya saja
     */
    const ATTACHMENT_NPWP = 'attachment/contact/npwp';
    const ATTACHMENT_FILE = 'attachment/contact/file';

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
