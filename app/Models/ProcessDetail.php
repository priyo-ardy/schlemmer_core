<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessDetail extends Model
{
    use HasFactory, Blameable, HasActivityLogs;

    protected $table = 'process_function_details';

    protected $fillable = [
        'header_id',
        'order',
        'requirements',
        'potential_failure_mode',
        'potential_effect_of_failure',
        'potential_cause_of_failure',
        'controls_prevention',
        'controls_detection',
        'created_by',
        'updated_by',
    ];
}
