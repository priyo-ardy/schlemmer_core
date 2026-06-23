<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory, HasActivityLogs, Blameable, SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'uuid',
        'revision',
        'code',
        'name',
        'alias',
        'tax_number',
        'tier_level',
        'csr_reference_doc',
        'risk_profile',
        'email',
        'phone',
        'billing_address',
        'shipping_address',
        'is_active',
        'remark',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->uuid)) {
                $customer->uuid = (string) Str::uuid7();
            }
        });
    }
}
