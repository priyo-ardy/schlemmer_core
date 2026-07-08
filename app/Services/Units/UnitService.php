<?php

namespace App\Services\Units;

use App\Models\ChangeLogs;
use App\Repositories\Units\UnitRepository;
use App\Services\ChangeLogs\ChangeLogsService;
use App\Services\GenerateCode\AutoNumberService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class UnitService
{

    public function __construct(
        protected UnitRepository $unitRepo,
        protected ChangeLogsService $logService,
        protected AutoNumberService $autoService
    ) {}

    public function getAllData()
    {
        return $this->unitRepo->getAllData();
    }

    public function store(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $dataInsert = [
                    'category_id' => $data['category_id'],
                    'code' => $this->autoService->generate('unit'),
                    'symbol' => $data['symbol'],
                    'revision' => $data['revision'] ?? 0,
                    'name' => $data['name'],
                    'is_active' => $data['is_active'] ?? true,
                    'is_base_unit' => $data['is_base_unit'],
                    'conversion_factor' => $data['conversion_factor'],
                    'conversion_offset' => $data['conversion_offset'],
                    'decimal_places' => $data['decimal_places'],
                    'remark'    => $data['remark'] ?? null
                ];

                $insert = $this->unitRepo->create($dataInsert);

                $this->logService->store($insert, 'create', 'register new uom data', null, $insert->toArray());

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
                    'category_id' => $data['category_id'],
                    'name' => $data['name'],
                    'symbol' => $data['symbol'],
                    'revision'  => ($oldData->revision ?? 0) + 1,
                    'is_base_unit' => $data['is_base_unit'],
                    'conversion_factor' => $data['conversion_factor'],
                    'conversion_offset' => $data['conversion_offset'],
                    'decimal_places' => $data['decimal_places'],
                    'is_active' => $data['is_active'] ?? true,
                    'remark'    => $data['remark'] ?? null
                ];

                $this->unitRepo->update($id, $dataUpdate);
                $updated = $this->unitRepo->findById($id);

                $newData = $this->unitRepo->findById($id);

                $this->logService->store($newData, 'update', $data['reason'], $oldData->toArray(), $newData->toArray());

                activity('update_success')
                    ->causedBy(Auth::id())
                    ->performedOn($updated)
                    ->withProperties([
                        'input_id' => $id,
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

                $this->logService->store($unit, 'delete', 'Delete data', $unit->toArray(), null);

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

    public function massDelete(array $ids, string $reason)
    {
        try {
            return DB::transaction(function () use ($ids, $reason) {
                if (empty($ids)) {
                    throw new \Exception('No UoM data found provided.');
                }

                $units = $this->unitRepo->findManyByIds($ids);

                if ($units->isEmpty()) {
                    throw new \Exception('No customer data found for the provided IDs.');
                }

                foreach ($units as $unit) {
                    $oldData = $unit->toArray();

                    activity('mass_delete_unit')
                        ->causedBy(Auth::id())
                        ->causedBy($unit)
                        ->withProperties([
                            'input_id' => $ids,
                            'old_data' => $oldData,
                            'ip' => Request::ip(),
                        ])
                        ->log('Mass delete success: Successfully deleted multiple unit data');

                    $this->logService->store($unit, 'delete', $reason, $oldData, null);
                }

                $mass_delete = $this->unitRepo->deleteAll($ids);

                return $mass_delete;
            });
        } catch (\Exception $e) {
            Log::error('Failed to perform mass delete unit data: ' . $e->getMessage());
            activity('system_error')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $ids,
                    'error_message' => $e->getMessage(),
                    'input_data' => ['ids' => $ids],
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'ip' => Request::ip()
                ])
                ->log('Delete failed: failed to mass delete units data ' . $e->getMessage());
            throw $e;
        }
    }

    public function getLogsData(int $id)
    {
        try {
            return $this->logService->getLogsData($id, 'units');
        } catch (\Exception $e) {
            activity('get_logs_data')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $id,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip(),
                ])
                ->log('Load failed: Failed to load UoM history data');

            throw $e;
        }
    }

    public function getLists($search)
    {
        return $this->unitRepo->getLists($search);
    }
}
