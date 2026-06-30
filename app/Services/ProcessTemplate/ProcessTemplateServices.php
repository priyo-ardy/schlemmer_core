<?php

namespace App\Services\ProcessTemplate;

use App\Models\ProcessHeader;
use App\Repositories\ProcessTemplate\ProcessTemplateRepository;
use App\Services\ChangeLogs\ChangeLogsService;
use App\Services\ProcessRevision\ProcessRevisionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

use function Symfony\Component\Clock\now;

class ProcessTemplateServices
{
    protected $processRepo;
    protected $revisionService;

    public function __construct(
        ProcessTemplateRepository $processRepo,
        ProcessRevisionService $revisionService,
        protected ChangeLogsService $logService
    ) {
        $this->processRepo = $processRepo;
        $this->revisionService = $revisionService;
    }

    public function storedData(array $data)
    {
        $userId = Auth::id();

        try {
            return DB::transaction(function () use ($data, $userId) {
                $headerData = [
                    'name' => trim($data['name']),
                    'revision' => 0,
                    'remark' => !empty($data['remark']) ? trim($data['remark']) : 'Initial PFMEA',
                    'is_active' => true,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ];

                // Insert ke teble header
                $processFunction = $this->processRepo->storeHeader($headerData);

                $detailsData = collect($data['processItems'])
                    ->map(function ($item, $index) use ($processFunction) {
                        return [
                            'order' => $index + 1,
                            'previous_problem' => strtoupper(trim($item['previous_problem'] ?? '')),
                            'requirements' => trim($item['requirements']),
                            'potential_failure_mode' => trim($item['potential_failure_mode']),
                            'potential_effect_of_failure' => trim($item['potential_effect_of_failure']),
                            'potential_cause_of_failure' => trim($item['potential_cause_of_failure']),
                            'controls_prevention' => trim($item['controls_prevention']),
                            'controls_detection' => trim($item['controls_detection']),
                        ];
                    })
                    ->toArray();

                // insert ke table details
                $processDetails = $this->processRepo->storeDetails($processFunction, $detailsData);

                // Proses revision
                $this->revisionService->createSnapShot(
                    header: $processFunction,
                    action: 'CREATE',
                    reason: 'Initial PMFEA'
                );
                return $processFunction;
            });
        } catch (\Exception $e) {
            Log::error('Failed to save Process Function: ' . $e->getMessage());
            activity('system_error')
                ->causedBy($userId)
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

    public function updateData(int $id, array $data)
    {
        try {
            $idHeader = $id;
            return DB::transaction(function () use ($data, $idHeader) {
                $headerData = [
                    'name' => trim($data['name']),
                    'revision' => DB::raw('revision + 1'),
                    'remark' => trim($data['remark']),
                    'updated_by' => Auth::id()
                ];

                $updateHeader = $this->processRepo->updateHeader($idHeader, $headerData);

                $incomingDetails = collect($data['processItems']);
                $incomingIds = $incomingDetails->pluck('id')->filter()->toArray();
                $this->processRepo->deleteDetailsNotIn($idHeader, $incomingIds);

                $incomingDetails->each(function ($item, $index) use ($idHeader) {
                    $detailData = [
                        'header_id' => $idHeader, // Pastikan relasinya diset
                        'order' => $index + 1,
                        'previous_problem' => strtoupper(trim($item['previous_problem'] ?? '')),
                        'requirements' => trim($item['requirements']),
                        'potential_failure_mode' => trim($item['potential_failure_mode']),
                        'potential_effect_of_failure' => trim($item['potential_effect_of_failure']),
                        'potential_cause_of_failure' => trim($item['potential_cause_of_failure']),
                        'controls_prevention' => trim($item['controls_prevention']),
                        'controls_detection' => trim($item['controls_detection']),
                    ];

                    if (!empty($item['id'])) {
                        $this->processRepo->updateDetail($item['id'], $detailData);
                    } else {
                        $detailData['uuid'] = Str::uuid7();
                        $this->processRepo->createDetail($detailData);
                    }
                });

                // Proses revision
                $freshHeader = ProcessHeader::find($idHeader);
                $this->revisionService->createSnapShot(
                    header: $freshHeader,
                    action: 'UPDATE',
                    reason: !empty($data['remark']) ? trim($data['remark']) : null
                );

                return $updateHeader;
            });
        } catch (\Exception $e) {
            Log::error('Failed to save Process Function: ' . $e->getMessage());
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

    public function getLogs($headerId)
    {
        try {
            return $this->processRepo->getLogs($headerId);
        } catch (\Exception $e) {
            Log::error('Failed to delete process data');
            activity()
                ->causedBy(Auth::id())
                ->withProperties([
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip(),
                ])
                ->log('Bulk delete failed: Failed to perform bulk delete');
            throw $e;
        }
    }

    public function getLogDetail($logId)
    {
        try {
            return $this->processRepo->getLogDetail($logId);
        } catch (\Exception $e) {
            Log::error('Failed to delete process data');
            activity()
                ->causedBy(Auth::id())
                ->withProperties([
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip(),
                ])
                ->log('Bulk delete failed: Failed to perform bulk delete');
            throw $e;
        }
    }

    public function bulkDelete($ids)
    {
        try {
            return $this->processRepo->deleteAll($ids);
        } catch (\Exception $e) {
            Log::error('Failed to delete process data');
            activity()
                ->causedBy(Auth::id())
                ->withProperties([
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip(),
                ])
                ->log('Bulk delete failed: Failed to perform bulk delete');
            throw $e;
        }
    }

    public function removeRow($id)
    {
        try {
            return ProcessHeader::where('id', $id)->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete process data');
            activity()
                ->causedBy(Auth::id())
                ->withProperties([
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip(),
                ])
                ->log('Bulk delete failed: Failed to perform bulk delete');
            throw $e;
        }
    }

    public function deleteAll(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                if (empty($data)) {
                    throw new \Exception('Mass delete failed, no process function data provided');
                }

                $process = $this->processRepo->findManyByIds($data['ids']);

                if ($process->isEmpty()) {
                    throw new \Exception('No data found from provided process function data, delete failed');
                }

                foreach ($process as $prc) {
                    $oldData = $this->processRepo->getHeaderById($prc->id);

                    $this->logService->store($prc, 'delete', trim($data['remark']), $oldData->toArray(), null);
                    activity()
                        ->causedBy(Auth::id())
                        ->performedOn($prc)
                        ->withProperties([
                            'input_id' => $prc->id,
                            'ip' => Request::ip()
                        ])
                        ->log('Delete success: Successfully deleted process functiond data');
                }

                $this->processRepo->deleteAll($data['ids']);

                return true;
            });
        } catch (\Exception $e) {
            activity('mass_delete_process_function')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $data['ids'],
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),
                    'ip' => Request::ip()
                ])
                ->log('Delete failed: failed to perform mass delete action');

            throw $e;
        }
    }

    public function getLogsData(int $id)
    {
        try {
            return $this->logService->getLogsData($id, 'process_functions');
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
