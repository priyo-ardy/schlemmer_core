<?php

namespace App\Models;

use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessChangeLogRevision extends Model
{
    use HasFactory, HasActivityLogs;

    protected $table = 'process_revision_logs';
    protected $fillable = [
        'header_id',
        'process_function_headers',
        'revision',
        'action',
        'change_reason',
        'created_by',

    ];

    public function revisionHeader(): BelongsTo
    {
        return $this->belongsTo(
            ProcessHeaderRevision::class,
            'header_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
