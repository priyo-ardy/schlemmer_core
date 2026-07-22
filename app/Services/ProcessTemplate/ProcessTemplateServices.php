<?php

namespace App\Services\ProcessTemplate;

use App\Jobs\ProcessFunctionQueueJob;
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
                $process_id = $data['process_id'];
                [$processParent, $processChild] = array_pad(explode('.', $process_id), 2, null);

                $processParent = (int) $processParent;
                $processChild = $processChild !== null ? (int) $processChild : null;

                // Buat update
                // if ($this->processRepo->existsByParentAndChild($parent, $child, $id)) {
                //     throw new Exception('Process ID already registered');
                // }

                $headerData = [
                    'name' => trim($data['name']),
                    'process_parent' => $processParent,
                    'process_child' => $processChild,
                    'sequence' => $this->processRepo->getLasSequence() + 1,
                    'revision' => 0,
                    'remark' => !empty($data['remark']) ? trim($data['remark']) : 'Initial PFMEA',
                    'is_active' => true,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ];

                // Insert ke teble header
                $processFunction = $this->processRepo->storeHeader($headerData);


                $detailsData = collect($data['processItems'])
                    ->map(function ($item, $index) {
                        $severity   = (int) ($item['severity'] ?? 0);
                        $occurrence = (int) ($item['occurrence'] ?? 0);
                        $detection  = (int) ($item['detection'] ?? 0);

                        $rpn = ($severity > 0 && $occurrence > 0 && $detection > 0)
                            ? ($severity * $occurrence * $detection)
                            : 0;

                        $result_severity   = (int) ($item['result_severity'] ?? 0);
                        $result_occurrence = (int) ($item['result_occurrence'] ?? 0);
                        $result_detection  = (int) ($item['result_detection'] ?? 0);

                        $result_rpn = ($result_severity > 0 && $result_occurrence > 0 && $result_detection > 0)
                            ? ($result_severity * $result_occurrence * $result_detection)
                            : 0;

                        return [
                            'order'                        => $index + 1,
                            'uuid'                         => $item['uuid'] ?? (string) Str::uuid7(),
                            'previous_problem'             => strtoupper(trim($item['previous_problem'] ?? '')),
                            'requirements'                 => trim($item['requirements'] ?? ''),
                            'potential_failure_mode'       => trim($item['potential_failure_mode'] ?? ''),
                            'potential_effect_of_failure'  => trim($item['potential_effect_of_failure'] ?? ''),
                            'potential_cause_of_failure'   => trim($item['potential_cause_of_failure'] ?? ''),
                            'classification'               => !empty($item['classification']) ? trim($item['classification']) : '',
                            'occurrence'                   => $occurrence,
                            'detection'                    => $detection,
                            'rpn'                          => $rpn,
                            'recommended_action'           => !empty($item['recommended_action']) ? trim($item['recommended_action']) : 'None',
                            'severity'                     => $severity,

                            // FIX: Pindahkan ?? '' ke dalam fungsi trim agar aman dari error undefined/null
                            'controls_prevention'          => trim($item['controls_prevention'] ?? ''),
                            'controls_detection'           => trim($item['controls_detection'] ?? ''),

                            'responsibility'               => $item['responsibility'] ?? null,
                            'target_completion_date'       => $item['target_completion_date'] ?? null,
                            'action_taken_completion_date' => $item['action_taken_completion_date'] ?? null,
                            'result_severity'              => $result_severity,
                            'result_occurrence'            => $result_occurrence,
                            'result_detection'             => $result_detection,
                            'result_rpn'                   => $result_rpn,
                            'updated_at'                   => now(),
                            'updated_by'                   => Auth::id()
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

                $dataLogs = [
                    'header' => $processFunction->toArray(),
                    'details' => $processDetails->toArray()
                ];

                $this->logService->store($processFunction, 'create', 'Initialize new process function data', null, $dataLogs);
                activity('save_process_function_header')
                    ->causedBy(Auth::id())
                    ->performedOn($processFunction)
                    ->withProperties([
                        'header_data' => $processFunction->toArray(),
                        'detail_data' => $processDetails->toArray(),
                        'ip' => Request::ip()
                    ])
                    ->log('Save success: Successfully saved new process funtion header data');

                ProcessFunctionQueueJob::dispatch('create', [])->afterCommit();

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
            return DB::transaction(function () use ($id, $data) {
                $process_id = $data['process_id'];
                [$processParent, $processChild] = array_pad(explode('.', $process_id), 2, null);

                $processParent = (int) $processParent;
                $processChild = $processChild !== null ? (int) $processChild : null;
                $oldHeaderData = $this->processRepo->getHeaderById($id);
                $oldDetailData = $this->processRepo->getDetailData($id);

                $nextRevision = ($oldHeaderData->revision ?? 0) + 1;

                // Cek apakah process parent & process child sudah ada
                $checkProcessId = $this->processRepo->existsByParentAndChild($processParent, $processChild, $id);
                if ($checkProcessId) {
                    throw new \Exception("Process ID already registered");
                }

                $headerData = [
                    'process_parent'    => $processParent,
                    'process_child'     => $processChild,
                    'name'              => trim($data['name']),
                    'revision'          => $nextRevision,
                    'remark'            => trim($data['remark'] ?? ''),
                    'updated_by'        => Auth::id()
                ];

                $freshHeader = $this->processRepo->updateHeader($id, $headerData);

                $incomingDetails = collect($data['processItems'] ?? []);
                $incomingIds = $incomingDetails->pluck('id')->filter()->toArray();
                $this->processRepo->deleteDetailsNotIn($id, $incomingIds);

                $detailsToSave = $incomingDetails->map(function ($item, $index) use ($id) {
                    $severity   = (int) ($item['severity'] ?? 0);
                    $occurrence = (int) ($item['occurrence'] ?? 0);
                    $detection  = (int) ($item['detection'] ?? 0);

                    $rpn = ($severity > 0 && $occurrence > 0 && $detection > 0)
                        ? ($severity * $occurrence * $detection)
                        : 0;

                    $result_severity   = (int) ($item['result_severity'] ?? 0);
                    $result_occurrence = (int) ($item['result_occurrence'] ?? 0);
                    $result_detection  = (int) ($item['result_detection'] ?? 0);

                    $result_rpn = ($result_severity > 0 && $result_occurrence > 0 && $result_detection > 0)
                        ? ($result_severity * $result_occurrence * $result_detection)
                        : 0;
                    return [
                        'id'                           => $item['id'] ?? null, // Jadi acuan MySQL untuk INSERT atau UPDATE
                        'header_id'                    => $id,
                        'order'                        => $index + 1,
                        'uuid'                         => $item['uuid'] ?? (string) Str::uuid7(),
                        'previous_problem'             => strtoupper(trim($item['previous_problem'] ?? '')),
                        'requirements'                 => trim($item['requirements'] ?? ''),
                        'potential_failure_mode'       => trim($item['potential_failure_mode'] ?? ''),
                        'potential_effect_of_failure'  => trim($item['potential_effect_of_failure'] ?? ''),
                        'potential_cause_of_failure'   => trim($item['potential_cause_of_failure'] ?? ''),
                        'classification'               => !empty($item['classification']) ? trim($item['classification']) : '',
                        'occurrence'                   => $occurrence,
                        'detection'                    => $detection,
                        'rpn'                          => $rpn,
                        'recommended_action'           => !empty($item['recommended_action']) ? trim($item['recommended_action']) : 'None',
                        'severity'                     => $severity,

                        // FIX: Pindahkan ?? '' ke dalam fungsi trim agar aman dari error undefined/null
                        'controls_prevention'          => trim($item['controls_prevention'] ?? ''),
                        'controls_detection'           => trim($item['controls_detection'] ?? ''),

                        'responsibility'               => $item['responsibility'] ?? null,
                        'target_completion_date'       => $item['target_completion_date'] ?? null,
                        'action_taken_completion_date' => $item['action_taken_completion_date'] ?? null,
                        'result_severity'              => $result_severity,
                        'result_occurrence'            => $result_occurrence,
                        'result_detection'             => $result_detection,
                        'result_rpn'                   => $result_rpn,
                        'updated_at'                   => now(),
                        'updated_by'                   => Auth::id()
                    ];
                })->toArray();

                if (!empty($detailsToSave)) {
                    $this->processRepo->upsertDetails($detailsToSave);
                }

                $freshDetail = $this->processRepo->getDetailData($id);

                $this->revisionService->createSnapShot(
                    header: $freshHeader,
                    action: 'UPDATE',
                    reason: !empty($data['reason']) ? trim($data['reason']) : null
                );

                $oldData = [
                    'header' => $oldHeaderData->toArray(),
                    'detail' => $oldDetailData->toArray()
                ];

                $newData = [
                    'header' => $freshHeader->toArray(),
                    'detail' => $freshDetail->toArray(),
                ];

                $this->logService->store($freshHeader, 'update', $data['reason'] ?? '', $oldData, $newData);

                // Buat persiapan nanti pas udah ada tabel transaksi pfmea nya
                ProcessFunctionQueueJob::dispatch('update', [])->afterCommit();

                return $freshHeader;
            });
        } catch (\Exception $e) {
            Log::error('Failed to save Process Function: ' . $e->getMessage());
            activity('system_error')
                ->causedBy(Auth::id())
                ->withProperties([
                    'error_message' => $e->getMessage(),
                    'input_data'    => $data,
                    'message'       => $e->getMessage(),
                    'file'          => $e->getFile(),
                    'line'          => $e->getLine(),
                    'trace'         => $e->getTraceAsString(),
                    'ip'            => request()->ip()
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
                    $this->logService->store($prc, 'delete', trim($data['remark']), $prc->toArray(), null);
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

                // ini buat proses ngapus proses di table pfmea kalo datanya mau didelete
                ProcessFunctionQueueJob::dispatch('delete', [])->afterCommit();

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
                    'trace' => $e->getTraceAsString(),
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

    public function searchProcess($search)
    {
        try {
            return $this->processRepo->searchProcess($search);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
