<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalTransaction extends Model
{
    use HasFactory;

    protected $table = 'approval_transactions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'uuid',
        'event',
        'transaction_type',
        'approver_id',
        'transaction_id',
        'approval_status',
        'remark',
        'approved_date'
    ];

    protected $casts = [
        'approval_status' => 'boolean',
        'approved_date' => 'datetime'
    ];

    public function transaction(): MorphTo
    {
        return $this->morphTo();
    }
}
