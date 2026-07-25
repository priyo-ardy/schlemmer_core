<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\HasActivityLogs;
use App\Notifications\Auth\QueuedResetPassword;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasActivityLogs, HasFactory, HasRoles, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'revision',
        'name',
        'email',
        'password',
        'avatar',
        'login_attempts',
        'is_locked',
        'is_active',
        'status',
        'last_login_at',
        'last_login_ip',
        'metadata',
    ];

    protected $casts = [
        'revision' => 'integer',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
        'login_attempts' => 'integer',
        'metadata' => 'array',
        'last_login_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_locked' => 'boolean',
            'login_attempts' => 'integer',
            'metadata' => 'array',
            'last_login_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        // Kirim notifikasi pake versi Queue kita
        $this->notify(new QueuedResetPassword($token));
    }

    protected static function booted()
    {
        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid7();
            }
        });
    }
}
