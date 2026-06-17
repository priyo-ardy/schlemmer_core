<?php

namespace App\Models;

use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
