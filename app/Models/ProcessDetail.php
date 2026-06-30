<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProcessDetail extends Model
{
    use HasFactory, Blameable, HasActivityLogs, HasUuids;

    protected $table = 'process_function_details';

    protected $fillable = [
        'uuid',
        'header_id',
        'order',
        'previous_problem',
        'requirements',
        'potential_failure_mode',
        'potential_effect_of_failure',
        'potential_cause_of_failure',
        'classification',
        'occurrence',
        'detection',
        'rpn',
        'recommended_action',
        'severity',
        'controls_prevention',
        'controls_detection',
        'created_by',
        'updated_by',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(ProcessHeader::class, 'header_id', 'id');
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }
}
