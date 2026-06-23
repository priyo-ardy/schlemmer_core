<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'priyo.ardy@schlemmer.co.id'],
            [
                'name' => 'Ardy Priyo Sudiyantoko',
                'password' => Hash::make('ardy9004'),
                'is_active' => true,
                'is_locked' => false,
                'login_attempts' => 0,
                'metadata' => [
                    'role' => 'superadmin',
                    'department' => 'IT Development',
                ],
            ]
        );

        User::updateOrCreate(
            ['email' => 'budi@schlemmer.co.id'],
            [
                'name' => 'Budi Security Flaw',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'is_locked' => true, // Akun sengaja dikunci dari awal
                'login_attempts' => 5,
                'metadata' => [
                    'locked_reason' => 'Brute force attack detected by system',
                ],
            ]
        );

        User::updateOrCreate(
            ['email' => 'siti@schlemmer.co.id'],
            [
                'name' => 'Siti Resigned',

                'password' => Hash::make('password123'),
                'is_active' => false,
                'is_locked' => false,
                'login_attempts' => 0,
            ]
        );
    }
}
