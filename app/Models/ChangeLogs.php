<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChangeLogs extends Model
{
    use HasFactory, Blameable;

    protected $table = 'change_logs_data';

    protected $fillable = [
        'uuid',
        'user_id',
        'table_name',
        'item_id',
        'event_name',
        'revision',
        'change_reason',
        'before',
        'after',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (ChangeLogs $logs) {
            if (empty($logs->uuid)) {
                $logs->uuid = (string) Str::uuid7();
            }
        });
    }
}
