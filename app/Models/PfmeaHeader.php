<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PfmeaHeader extends Model
{
    use HasFactory, Blameable, SoftDeletes, HasActivityLogs;

    protected $table = 'pfmea';

    protected $fillable = [
        'uuid',
        'code',
        'date',
        'department_id',
        'project_id',
        'version',
        'scope',
        'material_id',
        'process_responsibility',
        'prepared_by',
        'reviewed_by',
        'approved_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'version' => 'integer'
    ];

    public function details(): HasMany
    {
        return $this->hasMany(PfmeaDetail::class, 'pfmea_id');
    }

    public function coreTeam(): HasMany
    {
        return $this->hasMany(PfmeaCoreTeam::class, 'pfmea_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function booted()
    {
        static::creating(function (PfmeaHeader $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }
}
