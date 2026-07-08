<?php

namespace App\Services\UnitCategory;

use App\Repositories\UnitCategories\UnitCategoryRepository;
use App\Services\ChangeLogs\ChangeLogsService;
use App\Services\GenerateCode\AutoNumberService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class UnitCategoryService
{
    public function __construct(
        protected UnitCategoryRepository $categoryRepo,
        protected ChangeLogsService $logService,
        protected AutoNumberService $autoService
    ) {}

    public function getAllData()
    {
        return $this->categoryRepo->getAllData();
    }

    public function store(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $insertData = [
                    'code' => $this->autoService->generate('unit_category'),
                    'name' => trim($data['name']),
                    'description' => $data['description'] ? trim($data['description']) : null,
                    'sort_order' => 1,
                    'is_active' => $data['is_active'] ?? true,
                ];

                $insert = $this->categoryRepo->create($insertData);

                $this->logService->store($insert, 'create', 'register new unit category data', null, $insert->toArray());

                activity('save_unit_category')
                    ->causedBy(Auth::id())
                    ->performedOn($insert)
                    ->withProperties([
                        'data' => $insert->toArray(),
                        'ip' => Request::ip()
                    ]);

                return $insert;
            });
        } catch (\Exception $e) {
            activity('save_unit_category')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_data' => $data,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip()
                ])
                ->log('Save failed: Failed to save new unit category data');
            throw $e;
        }
    }

    public function update(int $id, array $data)
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $oldData = $this->categoryRepo->findById($id);

                $dataUpdate = [
                    'name' => trim($data['name']),
                    'revision' => ($oldData->revision ?? 0) + 1,
                    'description' => $data['description'] ? trim($data['description']) : null,
                    'is_active' => $data['is_active'] ?? true,
                ];

                $this->categoryRepo->update($id, $dataUpdate);

                $newData = $this->categoryRepo->findById($id);

                $this->logService->store($newData, 'update', $data['reason'], $oldData->toArray(), $newData->toArray());

                activity('update_unit_category')
                    ->causedBy(Auth::id())
                    ->performedOn($newData)
                    ->withProperties([
                        'input_id' => $id,
                        'old_data' => $oldData,
                        'new_data' => $newData,
                        'ip' => Request::ip()
                    ])->log('Update success: Unit category data updated successfully');
            });
        } catch (\Exception $e) {
            activity('update_unit_category')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $id,
                    'input_data' => $data,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip()
                ])
                ->log('Update failed: Failed to update unit category data');
            throw $e;
        }
    }

    public function massDelete(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                if (empty($data)) {
                    throw new \Exception('No categories data found');
                }

                $categories = $this->categoryRepo->findManyByIds($data['ids']);

                if ($categories->isEmpty()) {
                    throw new \Exception('No unit category data found for the provided IDs');
                }

                foreach ($categories as $category) {
                    $oldData = $this->categoryRepo->findById($category->id);

                    $this->logService->store($category, 'delete', $data['remark'], $oldData->toArray(), null);

                    activity('delete_unit_category')
                        ->causedBy(Auth::id())
                        ->performedOn($category)
                        ->withProperties([
                            'input_id' => $category->id,
                            'old_data' => $oldData,
                            'ip' => Request::ip()
                        ])
                        ->log('Delete success: Successfully deleting unit category data');
                }

                $this->categoryRepo->deleteAll($data['ids']);

                return true;
            });
        } catch (\Exception $e) {
            activity('delete_unit_category')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $data['ids'] ?? [],
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip()
                ])
                ->log('Mass delete failed: Failed to perform mass delete unit category data');

            throw $e;
        }
    }

    public function getLogsData(int $id)
    {
        try {
            return $this->logService->getLogsData($id, 'unit_categories');
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
                ->log('Load failed: Failed to load unit categories history data');

            throw $e;
        }
    }

    public function getList($search)
    {
        return $this->categoryRepo->getLists($search);
    }
}
