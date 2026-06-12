<?php

namespace App\Services\Users;

use App\Models\User;
use App\Repositories\User\UserRepository;
use Illuminate\Support\Facades\Hash;

class UserServices
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function registerUser(array $data): User
    {
        $data['login_attempt'] = 0;
        $data['is_locked'] = false;
        $data['is_active'] = true;

        if (!isset($data['avatar'])) {
            $data['avatar'] = 'https://ui-avatars.com/api/?name=' . urlencode($data['name']) . '&background=0D8ABC&color=fff';
        }

        return $this->userRepository->create($data);
    }
}
