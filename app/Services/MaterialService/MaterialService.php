<?php

namespace App\Services\MaterialService;

use App\Http\Resources\Material\MaterialResource;
use App\Models\Material;
use App\Models\MaterialLogs;
use App\Repositories\Material\MaterialRepository;
use App\Services\ChangeLogs\ChangeLogsService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class MaterialService
{
    protected MaterialRepository $materialRepo;

    public function __construct(
        MaterialRepository $materialRepo,
        protected ChangeLogsService $logService
    ) {
        $this->materialRepo = $materialRepo;
    }

    public function getMaterialList($filter, $per_page, $search = null)
    {
        try {
            return $this->materialRepo->getMaterialList($filter, $per_page, $search = null);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function store(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $dataInsert = [
                    'revision'              => 0,
                    'category'              => trim($data['category']),
                    'code'                  => trim($data['code']),
                    'name'                  => trim($data['name']),
                    'specification'         => trim($data['specification']),
                    'customer_part_name'    => $data['customer_part_name'] ? trim(strtoupper($data['customer_part_name'])) : null,
                    'unit_id'               => trim($data['unit_id']),
                    'grade'                 => trim($data['grade']) ?? null,
                    'density'               => trim($data['density']) ?? 0,
                    'melt_flow_index'       => trim($data['melt_flow_index']) ?? 0,
                    'color'                 => trim($data['color']) ?? null,
                    'drawing_change'        => trim($data['drawing_change']) ?? null,
                    'shrinkage_rate'        => trim($data['shrinkage_rate']) ?? null,
                    'gross_weight'          => trim($data['gross_weight']) ?? 0,
                    'net_weight'            => trim($data['net_weight']) ?? 0,
                    'sprue_weight'          => ($data['gross_weight'] >= $data['net_weight']) ? ($data['gross_weight'] - $data['net_weight']) : 0,
                    'has_rohs'              => $data['has_rohs'] ?? false,
                    'imds_number'           => trim($data['imds_number']) ?? null,
                    'msds_doc_path'         => trim($data['msds_doc_path']) ?? null,
                    'risk_profile'          => trim($data['risk_profile']) ?? 'low',
                    'is_active'             => $data['is_active'] ?? true,
                    'remark'                => trim($data['remark']) ?? null,
                ];

                $insert = $this->materialRepo->store($dataInsert);

                $this->logService->store($insert, 'create', 'Register new material data', null, $insert->toArray());

                activity('save_material')
                    ->causedBy(Auth::id())
                    ->performedOn($insert)
                    ->withProperties([
                        'input_data' => $insert->toArray(),
                        'ip' => Request::ip()
                    ])
                    ->log('Save success: Successfully saved new material data');

                return $insert;
            });
        } catch (\Exception $e) {
            Log::error('Failed to save new material data');
            activity()
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_data' => $data,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip()
                ])
                ->log('save error: failed to save new material data');

            throw $e;
        }
    }

    public function getMaterialData($id)
    {
        try {
            return $this->materialRepo->getDataById($id);
        } catch (\Exception) {
            Log::error('Material data not found');
            activity()
                ->causedBy(Auth::id())
                ->withProperties([
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ])
                ->log('retrieve error: failed to retrieve material data');

            throw $e;
        }
    }

    public function update(int $id, array $data)
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $oldData = $this->materialRepo->getDataById($id);

                if (empty($oldData)) {
                    throw new \Exception('Failed to update material data, material data not found');
                }

                $dataUpdate = [
                    'revision'              => ($oldData->revision ?? 0) + 1,
                    'category'              => trim($data['category']),
                    'code'                  => trim($data['code']),
                    'name'                  => trim($data['name']),
                    'specification'         => trim($data['specification']),
                    'customer_part_name'    => $data['customer_part_name'] ? trim(strtoupper($data['customer_part_name'])) : null,
                    'unit_id'               => trim($data['unit_id']),
                    'grade'                 => trim($data['grade']) ?? null,
                    'density'               => trim($data['density']) ?? 0,
                    'melt_flow_index'       => trim($data['melt_flow_index']) ?? 0,
                    'color'                 => trim($data['color']) ?? null,
                    'drawing_change'        => trim($data['drawing_change']) ?? null,
                    'shrinkage_rate'        => trim($data['shrinkage_rate']) ?? null,
                    'gross_weight'          => trim($data['gross_weight']) ?? 0,
                    'net_weight'            => trim($data['net_weight']) ?? 0,
                    'sprue_weight'          => ($data['gross_weight'] >= $data['net_weight']) ? ($data['gross_weight'] - $data['net_weight']) : 0,
                    'has_rohs'              => $data['has_rohs'] ?? false,
                    'imds_number'           => trim($data['imds_number']) ?? null,
                    'msds_doc_path'         => trim($data['msds_doc_path']) ?? null,
                    'risk_profile'          => trim($data['risk_profile']) ?? 'low',
                    'is_active'             => $data['is_active'] ?? true,
                    'remark'                => trim($data['remark']) ?? null,
                ];

                $this->materialRepo->update($id, $dataUpdate);

                $newData = $this->materialRepo->getDataById($id);

                $this->logService->store($newData, 'update', $data['reason'] ? trim($data['reason']) : 'Update material data', $oldData->toArray(), $newData->toArray());

                activity('update_material')
                    ->causedBy(Auth::id())
                    ->performedOn($newData)
                    ->withProperties([
                        'input_id' => $id,
                        'old_data' => $oldData->toArray(),
                        'new_data' => $newData->toArray(),
                        'ip' => Request::ip()
                    ])
                    ->log('Update success: Successfully updated material data');

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to update material data');
            activity()
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $id,
                    'inpit_data' => $data,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip()
                ])
                ->log('save error: failed to update material data');

            throw $e;
        }
    }

    public function bulkDelete(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                if (empty($data)) {
                    throw new \Exception('Delete failed, no material data provided');
                }

                $materials = $this->materialRepo->findManyByIds($data['ids']);

                if ($materials->isEmpty()) {
                    throw new \Exception('No material data found');
                }

                foreach ($materials as $material) {
                    $oldData = $this->materialRepo->getDataById($material->id);

                    $this->logService->store($material, 'delete', trim($data['remark']), $oldData->toArray(), null);

                    activity('delete_material')
                        ->causedBy(Auth::id())
                        ->performedOn($material)
                        ->withProperties([])
                        ->log('Delete success: Successfully delete material data');
                }

                $this->materialRepo->deleteAll($data['ids']);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to bulk delete material data');
            activity('delete_material')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $data['ids'],
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ])
                ->log('Delete failed: Failed to delete material data');

            throw $e;
        }
    }

    public function getLogsData($id)
    {
        try {
            return $this->logService->getLogsData($id, 'materials');
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
                ->log('Load failed: Failed to load material history data');

            throw $e;
        }
    }

    public function searchMaterial($search)
    {
        $materials = Material::query()
            ->when($search, function ($query, $search) {
                return $query->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%");
            })
            ->where('is_active', 1)
            ->paginate(5000);

        return $materials;
    }
}
