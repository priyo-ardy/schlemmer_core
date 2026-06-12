<?php

namespace App\Repositories\Contracts;


use App\Models\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function incrementAttempts(User $user): void;
    public function lockAccount(User $user): void;
    public function resetAttempts(User $user): void;
    public function updateLoginSuccess(User $user, string $ip): void;
    public function updatePassword($user, string $password): void;
}
