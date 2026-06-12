<?php

namespace App\Services\Auth;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthService
{
    protected UserRepositoryInterface $userRepository;
    protected const MAX_ATTEMPTS = 5;

    // String dummy bcrypt untuk mengecoh Timing Attack
    // protected const DUMMY_HASH = '$2y$10$IL4o77A6eN6E.VByE7bJ9unK.rNf7z7A6O6v7V7z7A6O6v7V7z7A6';
    protected const DUMMY_HASH = '$2y$12$p.YcxTEmQsTY.cHfT1lLyuS9RpeMgj4qiFPAXbUMamu5klDHJh92G';

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function login(array $credentials, string $ip): void
    {
        $user = $this->userRepository->findByEmail($credentials['email']);



        // 1. Antisipasi Timing Attack: Jika user tidak ada, tetap jalankan Hash::check dengan dummy string
        if (!$user || !$user->is_active) { // Catatan: sesuaikan dengan kolom 'status' di migrasi sebelumnya
            Hash::check($credentials['password'], self::DUMMY_HASH);

            throw ValidationException::withMessages([
                'email' => __('auth.failed')
            ]);
        }

        // 2. Cek apakah akun dikunci administrator
        if ($user->is_locked) {
            activity('auth')
                ->performedOn($user)
                ->withProperties(['ip' => $ip, 'status' => 'rejected'])
                ->log('Login rejected: Account is locked.');

            throw ValidationException::withMessages([
                'email' => 'This account has been locked. Please contact the administrator for unlock.',
            ]);
        }

        // 3. Verifikasi Password
        if (!Hash::check($credentials['password'], $user->password)) {
            // Naikkan hitungan di DB & memori ($user->login_attempts otomatis bertambah)
            $this->userRepository->incrementAttempts($user);

            // Audit Log: Catat setiap kegagalan login
            activity('auth')
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties(['ip' => $ip, 'attempt' => $user->login_attempts])
                ->log('Failed login attempt.');

            // Fix Logical Bug: Cek nilai asli setelah di-increment
            if ($user->login_attempts >= self::MAX_ATTEMPTS) {
                $this->userRepository->lockAccount($user);

                // Audit Log: Catat saat sistem otomatis mengunci akun
                activity('auth')
                    ->performedOn($user)
                    ->causedBy($user)
                    ->withProperties(['ip' => $ip])
                    ->log('Account automatically locked due to consecutive login failures.');

                throw ValidationException::withMessages([
                    'email' => 'This account has been locked due to too many failed login attempts. Please contact the administrator.'
                ]);
            }

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // 4. Login Sukses
        Auth::login($user, $credentials['remember'] ?? false);

        // Update data login dan RESET attempts ke 0
        $this->userRepository->updateLoginSuccess($user, $ip);

        // Audit Log: Login sukses
        activity('auth')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['ip' => $ip])
            ->log('User logged in successfully.');
    }

    public function sendResetLink(array $data): void
    {
        $user = $this->userRepository->findByEmail($data['email']);
        $status = Password::broker()->sendResetLink(['email' => $data['email']]);

        if ($status !== Password::RESET_LINK_SENT) {
            if ($user) {
                activity('auth')
                    ->performedOn($user)
                    ->causedBy($user)
                    ->withProperties([
                        'message' => 'The user requested a password reset link but system failed to send it.',
                        'ip' => request()->ip()
                    ])
                    ->log('Reset link : Failed');
            }

            throw ValidationException::withMessages([
                'email' => __($status)
            ]);
        }

        activity('auth')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'message' => 'Password reset link has been successfully sent to ' . $data['email'],
                'ip' => request()->ip()
            ])
            ->log('Reset link : Sent');
    }

    public function resetPassword(array $data): void
    {
        $status = Password::broker()->reset(
            $data,
            function ($user, $password) {
                $this->userRepository->updatePassword($user, $password);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status)
            ]);
        }
    }
}
