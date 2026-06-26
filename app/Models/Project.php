<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, HasActivityLogs, Blameable, SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'customer_id',
        'vehicle_model',
        'main_part_number',
        'main_part_name',
        'apqp_phase',
        'status',
        'kick_off_date',
        'target_proto_date',
        'target_ppap_date',
        'target_sop_date',
        'confidentiality_level',
        'revision',
        'is_active',
        'remark',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'kick_off_date' => 'date',
        'target_proto_date' => 'date',
        'target_ppap_date' => 'date',
        'target_sop_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public static function booted()
    {
        static::creating(function (Project $project) {
            if (empty($project->uuid)) {
                $project->uuid = (string) Str::uuid7();
            }
        });
    }
}
