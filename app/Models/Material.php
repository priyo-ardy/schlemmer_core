<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Material extends Model
{
    use HasFactory, HasActivityLogs, Blameable, SoftDeletes;

    protected $table = 'materials';

    protected $fillable = [
        'uuid',
        'revision',
        'category',
        'code',
        'name',
        'specification',
        'customer_part_name',
        'unit_id',
        'grade',
        'density',
        'melt_flow_index',
        'color',
        'drawing_change',
        'shrinkage_rate',
        'gross_weight',
        'net_weight',
        'sprue_weight',
        'has_rohs',
        'imds_number',
        'msds_doc_path',
        'risk_profile',
        'is_active',
        'remark',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'has_rohs' => 'boolean',
        'is_active' => 'boolean',
        'gross_weight' => 'decimal:4',
        'net_weight' => 'decimal:4',
        'sprue_weight' => 'decimal:4',
        'density' => 'decimal:4',
        'melt_flow_index' => 'decimal:4',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function units(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    protected static function booted()
    {
        static::creating(function (Material $material) {
            if (empty($material->uuid)) {
                $material->uuid = (string) Str::uuid7();
            }
        });
    }
}
