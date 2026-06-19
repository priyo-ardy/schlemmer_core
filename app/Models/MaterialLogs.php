<?php

namespace App\Models;

use App\HasActivityLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialLogs extends Model
{
    use HasFactory, HasActivityLogs;

    protected $table = 'material_logs';

    protected $fillable = [
        'material_id',
        'user_id',
        'event_type',
        'change_reason',
        'old_data',
        'new_data',
        'revision'
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
