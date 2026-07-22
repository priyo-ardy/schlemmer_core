<?php

namespace App\Models;

use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessHeaderRevision extends Model
{
    use HasFactory, HasActivityLogs;

    protected $table = 'process_revision_headers';

    protected $fillable = [
        'uuid',
        'header_id',
        'sequence',
        'process_parent',
        'process_child',
        'revision',
        'name',
        'remark',
        'change_reason',
        'created_by',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(ProcessDetailRevision::class, 'revision_header_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
