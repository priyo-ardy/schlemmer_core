<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProcessHeader extends Model
{
    use HasFactory, HasActivityLogs, Blameable, HasUuids, SoftDeletes;

    protected $table = 'process_functions';

    protected $fillable = [
        'uuid',
        'sequence',
        'process_parent',
        'process_child',
        'name',
        'revision',
        'remark',
        'is_active',
        'control_detection',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'sequence' => 'integer',
        'process_parent' => 'integer',
        'process_child' => 'integer',
        'revision' => 'integer',
        'is_active' => 'boolean'
    ];

    public function details(): HasMany
    {
        return $this->hasMany(ProcessDetail::class, 'header_id', 'id');
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
