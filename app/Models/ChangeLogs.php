<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeLogs extends Model
{
    use HasFactory, Blameable;

    protected $table = 'change_logs';

    protected $fillable = [
        'detail_item_id',
        'text_before',
        'text_after',
        'revision',
        'created_by',
        'updated_by'
    ];
}
