<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class UnitCategory extends Model
{
    use HasFactory, HasActivityLogs, Blameable, SoftDeletes;

    protected $table = 'unit_categories';

    protected $fillable = [
        'uuid',
        'revision',
        'code',
        'name',
        'description',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function booted()
    {
        static::creating(function (UnitCategory $category) {
            if (empty($category->uuid)) {
                $category->uuid = (string) Str::uuid7();
            }
        });
    }
}
