<?php

namespace App\Models;

use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessDetailRevision extends Model
{
    use HasFactory, HasActivityLogs;

    protected $table = 'process_revision_details';

    protected $fillable = [
        'revision_header_id',
        'detail_uuid',
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
        'responsibility',
        'target_completion_date',
        'action_taken_completion_date',
        'result_severity',
        'result_occurrence',
        'result_detection',
        'result_rpn'
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(ProcessHeaderRevision::class, 'revision_header_id', 'id');
    }
}
