<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Unit extends Model
{
    use HasFactory, SoftDeletes, HasActivityLogs, Blameable;

    protected $table = 'units';

    protected $fillable = [
        'symbol',
        'revision',
        'name',
        'is_active',
        'remark',
        'category_id',
        'code',
        'is_base_unit',
        'conversion_factor',
        'conversion_offset',
        'created_by',
        'updated_by'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted()
    {
        static::creating(function (Unit $unit) {
            if (empty($unit->uuid)) {
                $unit->uuid = (string) Str::uuid7();
            }
        });
    }
}
