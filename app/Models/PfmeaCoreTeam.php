<?php

namespace App\Models;

use App\Blameable;
use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PfmeaCoreTeam extends Model
{
    use HasFactory, HasActivityLogs, Blameable;

    protected $table = 'pfmea_core_teams';

    protected $fillable = [
        'uuid',
        'pfmea_id',
        'order',
        'user_id',
        'created_by',
        'updated_by',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(PfmeaHeader::class, 'pfmea_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted()
    {
        static::creating(function (PfmeaCoreTeam $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }
}
