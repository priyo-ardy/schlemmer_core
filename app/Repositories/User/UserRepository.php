<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function incrementAttempts(User $user): void
    {
        $user->increment('login_attempts');
    }

    public function lockAccount(User $user): void
    {
        $user->update([
            'is_locked' => true
        ]);
    }

    public function resetAttempts(User $user): void
    {
        $user->update([
            'login_attempts' => 0
        ]);
    }

    public function updateLoginSuccess(User $user, string $ip): void
    {
        $user->update([
            'login_attempts' => 0,
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    public function updatePassword($user, string $password): void
    {
        $user->update([
            'password' => Hash::make($password),
            'login_attempts' => 0,
            'is_locked' => false
        ]);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $user = User::find($id);

        if (!$user) {
            return false;
        }

        return $user->update($data);
    }

    public function delete(int $id): bool
    {
        $user = User::find($id);

        if (!$user) {
            return false;
        }

        return $user->delete();
    }
}
