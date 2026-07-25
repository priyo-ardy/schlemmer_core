<?php

namespace App\Services\AppRole;

use App\Repositories\AppRole\RoleRepository;
use App\Services\ChangeLogs\ChangeLogsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public function __construct(
        protected RoleRepository $roleRepo,
        protected ChangeLogsService $logService
    ) {}

    public function getAllRoles($perPage = 10, $search = null)
    {
        return $this->roleRepo->getAllRoles($perPage, $search);
    }

    public function getDataById(int $id)
    {
        return $this->roleRepo->getDataById($id);
    }

    public function storeRole(array $data)
    {
        try {
            $save = DB::transaction(function () use ($data) {
                return $this->roleRepo->storeRole($data);
            });

            $this->logService->store(
                $save,
                'create',
                'Register new user role data',
                null,
                $save->toArray()
            );

            activity('save_role')
                ->causedBy(Auth::user())
                ->performedOn($save)
                ->withProperties([])
                ->log('Save success: Successfully saved new user role data');

            return $save;

        } catch (\Exception $e) {
            activity('save_role')
                ->causedBy(Auth::user())
                ->withProperties([
                    'input_data' => $data,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => request()->ip(),
                ])
                ->log('Save failed: Failed to save new user role data.');

            throw $e;
        }
    }

    public function update(int $id, array $data)
    {
        try {
            // Ambil data lama untuk kebutuhan logging/changes jika diperlukan
            $oldData = $this->roleRepo->getDataById($id);

            $update = DB::transaction(function () use ($id, $data) {
                return $this->roleRepo->update($id, $data);
            });

            $this->logService->store(
                $update,
                'update',
                'Update user role data',
                $oldData->toArray(),
                $update->toArray()
            );

            activity('update_role')
                ->causedBy(Auth::user())
                ->performedOn($update)
                ->withProperties([])
                ->log('Update success: Successfully updated user role data');

            return $update;

        } catch (\Exception $e) {
            activity('update_role')
                ->causedBy(Auth::user())
                ->withProperties([
                    'id' => $id,
                    'input_data' => $data,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => request()->ip(),
                ])
                ->log('Update failed: Failed to update user role data.');

            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $role = $this->roleRepo->getDataById($id);

            DB::transaction(function () use ($id) {
                $this->roleRepo->delete($id);
            });

            $this->logService->store(
                $role,
                'delete',
                'Delete user role data',
                $role->toArray(),
                null
            );

            activity('delete_role')
                ->causedBy(Auth::user())
                ->performedOn($role)
                ->withProperties([])
                ->log('Delete success: Successfully deleted user role data');

            return true;

        } catch (\Exception $e) {
            activity('delete_role')
                ->causedBy(Auth::user())
                ->withProperties([
                    'id' => $id,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => request()->ip(),
                ])
                ->log('Delete failed: Failed to delete user role data.');

            throw $e;
        }
    }

    public function massDelete(array $ids)
    {
        try {
            DB::transaction(function () use ($ids) {
                $this->roleRepo->massDelete($ids);
            });

            activity('mass_delete_role')
                ->causedBy(Auth::user())
                ->withProperties(['ids' => $ids])
                ->log('Mass Delete success: Successfully deleted multiple user roles');

            return true;

        } catch (\Exception $e) {
            activity('mass_delete_role')
                ->causedBy(Auth::user())
                ->withProperties([
                    'ids' => $ids,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => request()->ip(),
                ])
                ->log('Mass Delete failed: Failed to delete multiple user roles.');

            throw $e;
        }
    }

    public function getLogs(int $id)
    {
        return $this->roleRepo->getLogs($id);
    }
}
