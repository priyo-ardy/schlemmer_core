<?php

namespace App\Services\Project;

use App\Models\Material;
use App\Models\ProjectMaterial;
use App\Repositories\Project\ProjectRepository;
use App\Services\ChangeLogs\ChangeLogsService;
use App\Services\Customer\CustomerService;
use App\Services\GenerateCode\AutoNumberService;
use App\Services\MaterialService\MaterialService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

use function Symfony\Component\Clock\now;

class ProjectService
{
    public function __construct(
        protected ProjectRepository $projectRepo,
        protected MaterialService $materialService,
        protected CustomerService $customerService,
        protected ChangeLogsService $logService,
    ) {}

    public function getAllData($filter, $per_page, $search = null)
    {
        return $this->projectRepo->getAllData($filter, $per_page, $search);
    }

    public function getDataById($id)
    {
        return $this->projectRepo->getDataById($id);
    }

    public function getDetails($id)
    {
        return $this->projectRepo->getDetails($id);
    }

    public function store(array $data)
    {
        try {
            if (empty($data['details'])) {
                throw new \Exception('No material data submitted');
            }

            return DB::transaction(function () use ($data) {
                $customer = $this->customerService->getDataByUuid($data['customer_id']);
                if (!$customer) {
                    throw new \Exception('The provided customer data is not available');
                }

                $dataInput = [
                    'code'                  => strtoupper(trim($data['code'])),
                    'name'                  => trim($data['name']),
                    'customer_id'           => $customer->id,
                    'vehicle_model'         => isset($data['vehicle_model']) ? trim($data['vehicle_model']) : null,
                    'main_part_number'      => isset($data['main_part_number']) ? trim($data['main_part_number']) : null,
                    'main_part_name'        => isset($data['main_part_name']) ? trim($data['main_part_name']) : null,
                    'apqp_phase'            => isset($data['apqp_phase']) ? trim($data['apqp_phase']) : null,
                    'status'                => isset($data['status']) ? trim($data['status']) : null,
                    'kick_off_date'         => $data['kick_off_date'] ?? null,
                    'target_proto_date'     => $data['target_proto_date'] ?? null,
                    'target_ppap_date'      => $data['target_ppap_date'] ?? null,
                    'target_sop_date'       => $data['target_sop_date'] ?? null,
                    'confidentiality_level' => isset($data['confidentiality_level']) ? trim($data['confidentiality_level']) : null,
                    'revision'              => 0,
                    'is_active'             => $data['is_active'] ?? 1,
                    'remark'                => !empty($data['remark']) ? trim($data['remark']) : null,
                ];

                // 1. Simpan data header terlebih dahulu
                $insert = $this->projectRepo->store($dataInput);

                // 2. Siapkan dan simpan data detail material
                $data_material = [];
                foreach ($data['details'] as $row) {
                    $material = $this->materialService->getDataByUuid($row['material_id']);

                    if (!$material) {
                        throw new \Exception('No material found for material UUID: ' . $row['material_id']);
                    }

                    $data_material[] = [
                        'material_id' => $material->id,
                        'created_by' => Auth::id(),
                    ];
                }
                $this->projectRepo->saveDetails($insert, $data_material);

                // 3. FIX BUG SEQUENCE: Eager load dipanggil SETELAH detail sukses tersimpan di DB
                $insert->load('customer, details.material');

                // 4. Bersihkan data array header dari nested detail bawaan model laravel
                $headerData = $insert->toArray();
                unset($headerData['details']);

                // 5. Susun Ulang $newInputData Secara Bersih & Flat
                $newInputData = [
                    'input_id' => $insert->id,
                    'header'   => $headerData,
                    'details'  => $insert->details->map(function ($detail) {
                        $item = $detail->toArray();
                        $item['material_code'] = $detail->material?->code ?? '-';
                        unset($item['material']);
                        return $item;
                    })->toArray()
                ];

                // 6. Jalankan Log Tunggal Komplit (Header + Details)
                $this->logService->store($insert, 'create', 'register new project data', null, $newInputData);

                activity('save_project')
                    ->causedBy(Auth::id())
                    ->performedOn($insert)
                    ->withProperties([
                        'input_data' => $newInputData,
                        'ip'         => Request::ip()
                    ])
                    ->log('Save success: successfully stored new project data');

                return $insert;
            });
        } catch (\Exception $e) {
            activity('save_project')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_data' => $data,
                    'message'    => $e->getMessage(),
                    'file'       => $e->getFile(),
                    'line'       => $e->getLine(),
                    'trace'      => $e->getTraceAsString()
                ])
                ->log('Save failed: Failed to save new project data');

            throw $e;
        }
    }

    public function update(int $id, array $data)
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $oldData = $this->projectRepo->findById($id);

                if (empty($oldData)) {
                    throw new \Exception('Failed to update project data, project data not found');
                }

                $oldData->load('details.material');
                $nextRevision = ($oldData->revision ?? 0) + 1;

                $customer = $this->customerService->getDataByUuid($data['customer_id']);
                if (!$customer) {
                    throw new \Exception("Customer not found in submitted data");
                }

                $updateData = [
                    'code'                  => strtoupper(trim($data['code'])),
                    'name'                  => trim($data['name']),
                    'customer_id'           => $customer->id,
                    'vehicle_model'         => $data['vehicle_model'] ? trim($data['vehicle_model']) : null,
                    'main_part_number'      => $data['main_part_number'] ? trim($data['main_part_number']) : null,
                    'main_part_name'        => $data['main_part_name'] ? trim($data['main_part_name']) : null,
                    'apqp_phase'            => $data['apqp_phase'] ? trim($data['apqp_phase']) : null,
                    'status'                => $data['status'] ? trim($data['status']) : null,
                    'kick_off_date'         => $data['kick_off_date'] ?? null,
                    'target_proto_date'     => $data['target_proto_date'] ?? null,
                    'target_ppap_date'      => $data['target_ppap_date'] ?? null,
                    'target_sop_date'       => $data['target_sop_date'] ?? null,
                    'confidentiality_level' => $data['confidentiality_level'] ? trim($data['confidentiality_level']) : null,
                    'revision'              => $nextRevision,
                    'is_active'             => $data['is_active'] ?? true,
                    'remark'                => $data['remark'] ? trim($data['remark']) : null,
                ];

                $updateHeader = $this->projectRepo->update($id, $updateData);

                if (!$updateHeader) {
                    throw new \Exception('Failed to update project data');
                }

                // Proses Sinkronisasi Detail
                $incomingDetails = collect($data['details'] ?? []);
                $incomingIds = $incomingDetails->pluck('id')->filter()->toArray();
                $this->projectRepo->deleteDetailsNotIn($id, $incomingIds);

                $detailsToSave = $incomingDetails->map(function ($item) use ($id) {
                    $materialId = $item['material_id'];
                    if (!is_numeric($materialId)) {
                        $materialId = Material::where('uuid', $materialId)->value('id');

                        if (!$materialId) {
                            throw new \Exception("Material dengan UUID {$item['material_id']} tidak ditemukan.");
                        }
                    }

                    return [
                        'id'          => $item['id'] ?? null,
                        'uuid'        => $item['uuid'] ?? (string) Str::uuid7(),
                        'project_id'  => $id,
                        'material_id' => $materialId,
                        'updated_by'  => Auth::id(),
                        'updated_at'  => now()
                    ];
                })->toArray();

                if (!empty($detailsToSave)) {
                    $this->projectRepo->upsertDetails($detailsToSave);
                }

                // 🟢 FIX BUG SEQUENCE: Ambil objek data baru SETELAH upsert detail selesai eksekusi
                $newHeader = $this->projectRepo->getDataById($id);
                $newHeader->load('details.material');

                // Hilangkan array details bawaan model agar tidak memicu [object Object] di loop header
                $headerOldData = $oldData->toArray();
                unset($headerOldData['details']);

                $headerNewData = $newHeader->toArray();
                unset($headerNewData['details']);

                $oldInputData = [
                    'input_id' => $id,
                    'header'   => $headerOldData,
                    'details'  => $oldData->details->map(function ($detail) {
                        $item = $detail->toArray();
                        $item['material_code'] = $detail->material?->code ?? '-';
                        unset($item['material']);
                        return $item;
                    })->toArray()
                ];

                $newInputData = [
                    'input_id' => $id,
                    'header'   => $headerNewData,
                    'details'  => $newHeader->details->map(function ($detail) {
                        $item = $detail->toArray();
                        $item['material_code'] = $detail->material?->code ?? '-';
                        unset($item['material']);
                        return $item;
                    })->toArray()
                ];

                $this->logService->store($newHeader, 'update', trim($data['reason']), $oldInputData, $newInputData);

                activity('update_project')
                    ->causedBy(Auth::id())
                    ->performedOn($newHeader)
                    ->withProperties([
                        'input_id' => $id,
                        'old_data' => $oldInputData,
                        'new_data' => $newInputData,
                        'ip'       => Request::ip()
                    ])
                    ->log('Update success: Successfully update project data');

                return true;
            });
        } catch (\Exception $e) {
            activity('update_project')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id'   => $id,
                    'input_data' => $data,
                    'message'    => $e->getMessage(),
                    'line'       => $e->getLine(),
                    'ip'         => Request::ip()
                ])
                ->log('Update failed: Failed to update project data');

            throw $e;
        }
    }

    public function deleteData(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $id_project = $data['id'];
                $reason = $data['reason'];

                $oldData = $this->projectRepo->getDataById($id_project);

                if (empty($oldData)) {
                    throw new \Exception('Failed to delete project data, project not found.');
                }

                // 🟢 FIX BUG SEQUENCE: Eager load dipasang pada $oldData SEBELUM records dihapus dari DB
                $oldData->load('details.material');

                $headerOldData = $oldData->toArray();
                unset($headerOldData['details']);

                $oldInputData = [
                    'input_id' => $id_project,
                    'header'   => $headerOldData,
                    'details'  => $oldData->details->map(function ($detail) {
                        $item = $detail->toArray();
                        $item['material_code'] = $detail->material?->code ?? '-';
                        unset($item['material']);
                        return $item;
                    })->toArray()
                ];

                // Eksekusi Hapus data setelah log state ter-capture dengan aman
                $this->projectRepo->deleteData($id_project);

                $this->logService->store($oldData, 'delete', $reason, $oldInputData, null);

                activity('delete_project')
                    ->causedBy(Auth::id())
                    ->performedOn($oldData)
                    ->withProperties([
                        'input_id' => $id_project,
                        'old_data' => $oldInputData,
                        'ip'       => Request::ip()
                    ])
                    ->log('Delete success: Successfully deleted project data');

                return true;
            });
        } catch (\Exception $e) {
            activity('delete_project')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $data['id'] ?? null,
                    'message'  => $e->getMessage(),
                    'file'     => $e->getFile(),
                    'line'     => $e->getLine(),
                    'trace'    => $e->getTraceAsString(),
                    'ip'       => Request::ip()
                ])
                ->log('Delete failed: Failed to delete project data');

            throw $e;
        }
    }

    public function deleteAll(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                if (empty($data['ids'])) {
                    throw new \Exception('Delete failed, no project data provided');
                }

                $projects = $this->projectRepo->findManyByIds($data['ids']);

                if ($projects->isEmpty()) {
                    throw new \Exception('No project data found');
                }

                foreach ($projects as $project) {
                    $oldData = $this->projectRepo->findById($project->id);

                    if ($oldData) {
                        // Eager load data lama sebelum dihapus massal
                        $oldData->load('details.material');

                        $headerOldData = $oldData->toArray();
                        unset($headerOldData['details']);

                        $oldInputData = [
                            'input_id' => $oldData->id,
                            'header'   => $headerOldData,
                            'details'  => $oldData->details->map(function ($detail) {
                                $item = $detail->toArray();
                                $item['material_code'] = $detail->material?->code ?? '-';
                                unset($item['material']);
                                return $item;
                            })->toArray()
                        ];

                        // Catat alasan (fallback ke reason jika remark kosong)
                        $deletionReason = trim($data['reason'] ?? $data['remark'] ?? 'Bulk delete project data');
                        $this->logService->store($project, 'delete', $deletionReason, $oldInputData, null);

                        activity('delete_project')
                            ->causedBy(Auth::id())
                            ->performedOn($project)
                            ->withProperties([
                                'input_id' => $project->id,
                                'ip'       => Request::ip()
                            ])
                            ->log('Delete success: Successfully delete project data');
                    }
                }

                $this->projectRepo->deleteAll($data['ids']);

                return true;
            });
        } catch (\Exception $e) {
            activity('delete_project')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $data['ids'] ?? null,
                    'message'  => $e->getMessage(),
                    'file'     => $e->getFile(),
                    'line'     => $e->getLine(),
                    'trace'    => $e->getTraceAsString()
                ])
                ->log('Delete failed: Failed to delete project data');

            throw $e;
        }
    }

    public function getLogsData(int $id)
    {
        try {
            return $this->logService->getLogsData($id, 'projects');
        } catch (\Exception $e) {
            activity('get_logs_data')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $id,
                    'message'  => $e->getMessage(),
                    'file'     => $e->getFile(),
                    'line'     => $e->getLine(),
                    'trace'    => $e->getTraceAsString(),
                    'ip'       => Request::ip(),
                ])
                ->log('Load failed: Failed to load project history data');

            throw $e;
        }
    }

    public function searchProject($search)
    {
        return $this->projectRepo->searchProject($search);
    }
}
