<?php

namespace App\Services\Project;

use App\Repositories\Project\ProjectRepository;
use App\Services\ChangeLogs\ChangeLogsService;
use App\Services\GenerateCode\AutoNumberService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class ProjectService
{
    public function __construct(
        protected ProjectRepository $projectRepo,
        protected ChangeLogsService $logService,
    ) {}

    public function getAllData($filter, $per_page, $search = null)
    {
        return $this->projectRepo->getAllData($filter, $per_page, $search);
    }

    public function store(array $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $dataInput = [
                    'code' => strtoupper(trim($data['code'])),
                    'name' => trim($data['name']),
                    'customer_id' => trim($data['customer']),
                    'vehicle_model' => trim($data['vehicle_model']),
                    'main_part_number' => trim($data['main_part_number']),
                    'main_part_name' => trim($data['main_part_number']),
                    'apqp_phase' => trim($data['apqp_phase']),
                    'status' => trim($data['status']),
                    'kick_off_date' => $data['kick_off_date'],
                    'target_proto_date' => $data['target_proto_date'],
                    'target_ppap_date' => $data['target_ppap_date'],
                    'target_sop_date' => $data['target_sop_date'],
                    'confidentiality_level' => trim($data['confidentiality_level']),
                    'revision' => 0,
                    'is_active' => $data['is_active'],
                    'remark' => $data['remark'] ? trim($data['remark']) : null,
                ];

                $insert = $this->projectRepo->store($dataInput);

                $this->logService->store($insert, 'create', 'register new project data', null, $insert->toArray());

                activity('save_project')
                    ->causedBy(Auth::id())
                    ->performedOn($insert)
                    ->withProperties([
                        'new_data' => $insert->toArray(),
                        'ip' => Request::ip()
                    ])
                    ->log('Save success: successfuly stored new project data');

                return $insert;
            });
        } catch (\Exception $e) {
            activity('save_project')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_data' => $data,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ])
                ->log('Save failed: Failed to save new project data');
            throw $e;
        }
    }


    public function update(int $id, array $data)
    {
        try {
            DB::transaction(function () use ($id, $data) {
                $oldData = $this->projectRepo->findById($id);

                if (empty($oldData)) {
                    throw new \Exception('Failed to update project data, project data not found');
                }

                $updateData = [
                    'code' => strtoupper(trim($data['code'])),
                    'name' => trim($data['name']),
                    'customer_id' => trim($data['customer']),
                    'vehicle_model' => trim($data['vehicle_model']),
                    'main_part_number' => trim($data['main_part_number']),
                    'main_part_name' => trim($data['main_part_number']),
                    'apqp_phase' => trim($data['apqp_phase']),
                    'status' => trim($data['status']),
                    'kick_off_date' => $data['kick_off_date'],
                    'target_proto_date' => $data['target_proto_date'],
                    'target_ppap_date' => $data['target_ppap_date'],
                    'target_sop_date' => $data['target_sop_date'],
                    'confidentiality_level' => trim($data['confidentiality_level']),
                    'revision' => ($oldData->revision ?? 0) + 1,
                    'is_active' => $data['is_active'],
                    'remark' => $data['remark'] ? trim($data['remark']) : null,
                ];

                $update = $this->projectRepo->update($id, $updateData);
                if (!$update) {
                    throw new \Exception('Failed to update project data');
                }

                $newData = $this->projectRepo->findById($id);

                $this->logService->store($newData, 'update', trim($data['reason']), $oldData->toArray(), $newData->toArray());

                return true;
            });
        } catch (\Exception $e) {
            activity('update_project')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $id,
                    'input_data' => $data,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ])
                ->log('Update failed: Failed to update project data');

            throw $e;
        }
    }

    public function deleteAll(array $data)
    {
        try {
            DB::transaction(function () use ($data) {
                if (empty($data)) {
                    throw new \Exception('Delete failed, no project data provided');
                }

                $projects = $this->projectRepo->findManyByIds($data['ids']);

                if ($projects->isEmpty()) {
                    throw new \Exception('No project data found');
                }

                foreach ($projects as $project) {
                    $oldData = $this->projectRepo->findById($project->id);

                    $this->logService->store($project, 'delete', trim($data['remark']), $oldData->toArray(), null);

                    activity('delete_project')
                        ->causedBy(Auth::id())
                        ->performedOn($project)
                        ->withProperties([
                            'input_id' => $project->id,
                            'ip' => Request::ip()
                        ])
                        ->log('Delete success: Successfully delete project data');
                }

                $this->projectRepo->deleteAll($data['ids']);

                return true;
            });
        } catch (\Exception $e) {
            activity('delete_project')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $data['ids'],
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ])
                ->log('Delete failed: Failed to delete project data');

            throw $e;
        }
    }

    public function getLogsData(int $id)
    {
        try {
            return $this->logService->getLogsData($id, 'project');
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
                ->log('Load failed: Failed to load project history data');

            throw $e;
        }
    }
}
