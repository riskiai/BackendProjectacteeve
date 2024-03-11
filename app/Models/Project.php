<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory;

    const ATTACHMENT_FILE = 'attachment/project/file';

    const STATUS_OPEN = 'OPEN';
    const STATUS_CLOSED = 'CLOSED';
    const STATUS_NEED_TO_CHECK = 'NEED TO CHECK';

    const PENDING = 1;
    const ACTIVE = 2;
    const REJECTED = 3;

    protected $table = 'projects';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'date',
        'name',
        'billing',
        'cost_estimate',
        'margin',
        'percent',
        'status_cost_progress',
        'file',
        'status',
    ];


    /* Ngebuat Data ID Bisa String */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = 'PRO-' . date('y') . '-' . $model->generateSequenceNumber();
        });
    }

    /* Ngebut Data Id Menjadi otomatis nambah */
    protected function generateSequenceNumber()
    {
        $lastId = static::max('id');
        $numericPart = (int) substr($lastId, strpos($lastId, '-0') + 1);
        $nextNumber = sprintf('%03d', $numericPart + 1);
        return $nextNumber;
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'project_id', 'id');
    }
}
