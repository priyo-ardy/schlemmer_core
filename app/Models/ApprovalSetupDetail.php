<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApprovalSetupDetail extends Model
{
    use HasFactory, Blameable, HasActivityLogs;

    protected $table = 'approval_setup_details';

    protected $fillable = [
        'uuid',
        'header_id',
        'order',
        'approver_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(ApprovalSetup::class, 'header_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function booted()
    {
        static::creating(function (ApprovalSetupDetail $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }
}
