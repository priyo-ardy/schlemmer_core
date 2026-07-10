<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProjectMaterial extends Model
{
    use HasFactory, HasActivityLogs, Blameable;

    protected $table = "project_materials";

    protected $fillable = [
        'uuid',
        'project_id',
        'material_id',
        'created_by',
        'updated_by',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    protected static function booted()
    {
        static::creating(function (ProjectMaterial $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }
}
