<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory, HasActivityLogs, Blameable, SoftDeletes;

    protected $table = 'materials';

    protected $fillable = [
        'part_number',
        'part_name',
        'revision',
        'material_type',
        'is_safety_part',
        'warehouse_code',
        'rack_code',
        'bin_code',
        'min_stock',
        'unit_of_measure',
        'created_by',
        'updated_by'
    ];
}
