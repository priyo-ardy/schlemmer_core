<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PfmeaDetail extends Model
{
    use HasFactory, HasActivityLogs, Blameable;

    protected $table = 'pfmea_details';

    protected $fillable = [
        'uuid',
        'pfmea_id',
        'order',
        'process_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'order' => 'integer'
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(PfmeaHeader::class, 'pfmea_id');
    }

    public function processFunction(): BelongsTo
    {
        return $this->belongsTo(ProcessHeader::class, 'process_id');
    }

    protected static function booted()
    {
        static::creating(function (PfmeaDetail $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }
}
