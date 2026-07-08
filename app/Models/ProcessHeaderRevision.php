<?php

namespace App\Models;

use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessHeaderRevision extends Model
{
    use HasFactory, HasActivityLogs;

    protected $table = 'process_revision_headers';

    protected $fillable = [
        'uuid',
        'header_id',
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
}
