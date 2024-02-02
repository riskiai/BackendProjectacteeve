<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory;

    const ATTACHMENT_FILE = 'attachment/project/file';

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
        'file',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = 'PRO-' . date('y') . '-' . $model->generateSequenceNumber();
        });
    }

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
}
