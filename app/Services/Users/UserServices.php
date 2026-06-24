<?php

namespace App\Services\Users;

use App\Models\User;
use App\Repositories\User\UserRepository;
use App\Services\ChangeLogs\ChangeLogsService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserServices
{


    public function __construct(
        protected UserRepository $userRepository,
        protected ChangeLogsService $logService
    ) {}

    public function getAllUsers(): Collection
    {
        return $this->userRepository->getAll();
    }

    public function registerUser(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                // 1. Inisialisasi Default Values
                $data['password']       = Hash::make($data['password']);
                $data['login_attempts'] = 0;
                $data['is_locked']      = false;
                $data['is_active']      = true;

                if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
                    $file = $data['avatar'];
                    // Tambahkan Str::random agar nama file 100% unik
                    $filename = Str::slug($data['name']) . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                    $path     = $file->storeAs('avatars', $filename, 'public');
                    $data['avatar'] = '/storage/' . $path;
                } else {
                    $data['avatar'] = null; // Pastikan benar-benar null
                }

                $save = $this->userRepository->create($data);

                $this->logService->store($save, 'create', 'register new user', null, $data);

                activity('save_user')
                    ->causedBy(Auth::id())
                    ->withProperties([
                        'user_id' => $save->id,
                        'ip'      => Request::ip()
                    ])
                    ->log('Save success: Successfully register new user data');

                return true;
            });
        } catch (\Exception $e) {
            activity('save_user')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_data' => $data,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ])
                ->log('Save failed: Failed to register new user data');
            throw $e;
        }
    }

    public function updateSecuritySettings(int $id, array $data): bool
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $user = User::findOrFail($id);

                $data['revision'] = $user->revision + 1;

                if (isset($data['is_locked'])) {
                    $data['is_locked'] = filter_var($data['is_locked'], FILTER_VALIDATE_BOOLEAN);

                    if ($data['is_locked'] === false) {
                        $data['login_attempts'] = 0;
                    }
                }

                if (isset($data['is_active'])) {
                    $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
                }

                if (!empty($data['password'])) {
                    $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
                } else {
                    unset($data['password']);
                }

                if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
                    // Hapus avatar lama
                    if ($user->avatar && str_contains($user->avatar, '/storage/avatars/')) {
                        $oldPath = str_replace('/storage/', '', $user->avatar);
                        Storage::disk('public')->delete($oldPath);
                    }

                    $file = $data['avatar'];
                    $filename = Str::slug($data['name'] ?? $user->name) . '-' . time() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('avatars', $filename, 'public');
                    $data['avatar'] = '/storage/' . $path;
                } else {
                    unset($data['avatar']);
                }

                $this->userRepository->update($id, $data);

                $new = User::findOrFail($id);

                $this->logService->store($new, 'update', 'Update user data', $user->toArray(), $new->toArray());

                activity('update_user')
                    ->causedBy(Auth::id())
                    ->withProperties([
                        'old_data' => $user,
                        'new_data' => $new,
                        'ip' => Request::ip()
                    ])
                    ->log('Update success: Successfully update user data');

                return true;
            });
        } catch (\Exception $e) {
            activity('update_user')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $id,
                    'input_data' => $data,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ])
                ->log('Update failed: Failed to update user data');
            throw $e;
        }
    }

    public function bulkDeleteUsers(array $ids, int $currentAdminId, string $reason)
    {
        try {
            return DB::transaction(function () use ($ids, $currentAdminId, $reason) {
                $sanitizedIds = array_diff($ids, [$currentAdminId]);

                if (empty($sanitizedIds)) {
                    return false;
                }

                $users = User::whereIn('id', $sanitizedIds)->get();
                foreach ($users as $user) {
                    $oldData = User::find($user->id);

                    if ($user->avatar && str_contains($user->avatar, '/storage/avatars/')) {
                        $oldPath = str_replace('/storage/', '', $user->avatar);
                        Storage::disk('public')->delete($oldPath);
                    }

                    activity('delete_user')
                        ->causedBy(Auth::id())
                        ->performedOn($user)
                        ->withProperties([
                            'input_id' => $user->id,
                            'old_data' => $oldData,
                            'ip' => Request::ip()
                        ])
                        ->log('Mass delete success: Successfully deleted user data');

                    $this->logService->store($user, 'delete', $reason, $oldData->toArray(), null);
                }

                $this->userRepository->bulkDelete($sanitizedIds);

                return true;
            });
        } catch (\Exception $e) {
            activity('mass_delete_customer')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $ids,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip(),
                ])
                ->log('Delete failed: Failed to mass delete user data');

            throw $e;
        }
    }
}
