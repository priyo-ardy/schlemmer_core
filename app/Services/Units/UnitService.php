<?php

namespace App\Services\Units;

use App\Repositories\Units\UnitRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class UnitService
{
    protected UnitRepository $unitRepo;

    public function __construct(UnitRepository $unitRepo)
    {
        $this->unitRepo = $unitRepo;
    }

    public function getAllData()
    {
        return $this->unitRepo->getAllData();
    }

    public function store(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $dataInsert = [
                    'symbol' => $data['symbol'],
                    'revision' => $data['revision'] ?? 0,
                    'name' => $data['name'],
                    'is_active' => $data['is_active'] ?? true,
                    'remark'    => $data['remark'] ?? null
                ];

                $insert = $this->unitRepo->create($dataInsert);
                activity('save_material')
                    ->causedBy(Auth::id())
                    ->performedOn($insert)
                    ->withProperties([
                        'data' => $dataInsert,
                        'ip' => Request::ip()
                    ])
                    ->log('Save success: successfully register new material data');

                return $insert;
            });
        } catch (\Exception $e) {
            Log::error('Failed to save new material data: ' . $e->getMessage());
            activity('system_error')
                ->causedBy(Auth::id())
                ->withProperties([
                    'error_message' => $e->getMessage(),
                    'input_data' => $data,
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ])
                ->log('Save failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $data)
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $oldData = $this->unitRepo->findById($id);
                $dataUpdate = [
                    'symbol' => $data['symbol'],
                    'revision'  => ($oldData->revision ?? 0) + 1,
                    'name' => $data['name'],
                    'is_active' => $data['is_active'] ?? true,
                    'remark'    => $data['remark'] ?? null
                ];

                $this->unitRepo->update($id, $dataUpdate);
                $updated = $this->unitRepo->findById($id);

                activity('update_success')
                    ->causedBy(Auth::id())
                    ->performedOn($updated)
                    ->withProperties([
                        'old_data' => $oldData,
                        'new_data' => $dataUpdate,
                        'ip' => Request::ip()
                    ])
                    ->log('Update success: successfully updated material data');

                return $updated;
            });
        } catch (\Exception $e) {
            Log::error('Failed to update material data: ' . $e->getMessage());
            activity('system_error')
                ->causedBy(Auth::id())
                ->withProperties([
                    'error_message' => $e->getMessage(),
                    'input_data' => $data,
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ])
                ->log('Save failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $unit = $this->unitRepo->findById($id);
                $deleted = $this->unitRepo->delete($id);

                activity('delete_success')
                    ->causedBy(Auth::id())
                    ->performedOn($unit)
                    ->withProperties([
                        'uuid' => $unit->uuid,
                        'ip' => Request::ip()
                    ])
                    ->log('Delete success: Successfully deleted unit data');

                return $deleted;
            });
        } catch (\Exception $e) {
            Log::error('Failed to delete unit data: ' . $e->getMessage());
            activity('system_error')
                ->causedBy(Auth::id())
                ->withProperties([
                    'error_message' => $e->getMessage(),
                    'input_data' => ['id' => $id],
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ])
                ->log('Delete failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function massDelete(array $ids)
    {
        try {
            return DB::transaction(function () use ($ids) {
                if (empty($ids)) {
                    return false;
                }

                $mass_delete = $this->unitRepo->deleteAll($ids);

                activity('mass_delete')
                    ->causedBy(Auth::id())
                    ->withProperties([
                        'ids' => $ids,
                        'ip' => Request::ip(),
                    ])
                    ->log('Mass delete success: Successfully deleted multiple unit data');

                return $mass_delete;
            });
        } catch (\Exception $e) {
            Log::error('Failed to perform mass delete unit data: ' . $e->getMessage());
            activity('system_error')
                ->causedBy(Auth::id())
                ->withProperties([
                    'error_message' => $e->getMessage(),
                    'input_data' => ['ids' => $ids],
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ])
                ->log('Delete failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
