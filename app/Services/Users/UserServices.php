<?php

namespace App\Services\Users;

use App\Models\User;
use App\Repositories\User\UserRepository;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserServices
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getAllUsers(): Collection
    {
        return $this->userRepository->getAll();
    }

    public function registerUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $data['login_attempts'] = 0;
        $data['is_locked'] = false;
        $data['is_active'] = true;

        // LOGIKA UPLOAD AVATAR (CREATE OPTIONAL)
        if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $data['avatar'];
            // Penamaan File: slug-nama-user + timestamp keunikan
            $filename = Str::slug($data['name']) . '-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('avatars', $filename, 'public');
            $data['avatar'] = '/storage/' . $path;
        } else {
            $data['avatar'] = null; // Jika kosong, biarkan null agar FE memicu inisial nama
        }

        return $this->userRepository->create($data);
    }

    public function updateSecuritySettings(int $id, array $data): bool
    {
        $user = User::findOrFail($id);

        if (isset($data['is_locked']) && $data['is_locked'] === false) {
            $data['login_attempts'] =  0;
        }

        if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
            if ($user->avatar && str_contains($user->avatar, '/storage/avatars/')) {
                $oldPath = str_replace('/storage/', '', $user->avatar);
                Storage::disk('public')->delete($oldPath);
            }

            $file = $data['avatar'];
            $filename = Str::slug($data['name']) . '-' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('avatars', $filename, 'public');
            $data['avatar'] = '/storage/' . $path;
        } else {
            unset($data['avatar']);
        }

        return $this->userRepository->update($id, $data);
    }

    public function bulkDeleteUsers(array $ids, int $currentAdminId): bool
    {
        // if (in_array($currentAdminId, $ids)) {
        //     throw new Exception("Security breach: operational blocks prevented, you cannot delete your own session account.");
        // }

        // return $this->userRepository->bulkDelete($ids);

        // 🛡️ PROTEKSI BACKEND SAKTI: 
        // Keluarkan ID admin yang sedang login dari antrean array penghapusan data
        $sanitizedIds = array_diff($ids, [$currentAdminId]);

        // Jika setelah disaring ternyata kosong (misal cuma mau nyoba hapus diri sendiri), langsung batalkan
        if (empty($sanitizedIds)) {
            return false;
        }

        // Hapus file foto fisik hanya untuk ID-ID yang valid tersisa
        $users = User::whereIn('id', $sanitizedIds)->get();
        foreach ($users as $user) {
            if ($user->avatar && str_contains($user->avatar, '/storage/avatars/')) {
                $oldPath = str_replace('/storage/', '', $user->avatar);
                Storage::disk('public')->delete($oldPath);
            }
        }

        // Eksekusi hapus database massal untuk sisa user lainnya
        return $this->userRepository->bulkDelete($sanitizedIds);
    }
}
