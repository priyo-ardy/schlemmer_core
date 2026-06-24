<?php

namespace App\Repositories\Contracts;


use App\Models\User;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function incrementAttempts(User $user): void;
    public function lockAccount(User $user): void;
    public function resetAttempts(User $user): void;
    public function updateLoginSuccess(User $user, string $ip): void;
    public function updatePassword($user, string $password): void;
    public function create(array $data): User;
    public function update(int $id, array $data): ?User;
    public function delete(int $id): bool;
    public function getAll(): Collection;
    public function bulkDelete(array $data): bool;
}
