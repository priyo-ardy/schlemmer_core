<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;
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
            'is_locked' => true,
        ]);
    }

    public function resetAttempts(User $user): void
    {
        $user->update([
            'login_attempts' => 0,
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
            'is_locked' => false,
        ]);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): ?User
    {
        $user = User::find($id);

        if (! $user) {
            return null;
        }
        $user->increment('revision');
        $user->update($data);

        return $user;
    }

    public function delete(int $id): bool
    {
        $user = User::find($id);

        if (! $user) {
            return false;
        }

        return $user->delete();
    }

    public function getAll(): Collection
    {
        return User::with('roles')->orderBy('name', 'asc')->get();
    }

    public function bulkDelete(array $ids): bool
    {
        return User::whereIn('id', $ids)->delete();
    }

    public function searchProcess($search)
    {
        $users = User::query()
            ->select('id', 'name')
            ->where('is_active', 1)
            ->where('is_locked', 0)
            ->when($search, function ($query, $search) {
                return $query->where('name', 'LIKE', "%{$search}%");
            })
            ->orderBy('name', 'asc')
            ->paginate(10);

        return $users;
    }
}
