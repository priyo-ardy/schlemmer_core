<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProcessHeader extends Model
{
    use HasFactory, HasActivityLogs, Blameable;

    protected $table = 'process_function';

    protected $fillable = [
        'uuid',
        'name',
        'revision',
        'remark',
        'is_active',
        'control_detection',
        'created_by',
        'updated_by'
    ];

    public function details(): HasMany
    {
        return $this->hasMany(ProcessDetail::class, 'header_id');
    }

    protected static function booted()
    {
        static::creating(function (ProcessHeader $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }
}
