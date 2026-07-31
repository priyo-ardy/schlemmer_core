<?php

namespace App\Services\PFMEA;

use App\Repositories\Department\DepartmentRepository;
use App\Repositories\PFMEA\PfmeaRepository;
use App\Services\ChangeLogs\ChangeLogsService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;

class PfmeaService
{
    public function __construct(
        protected PfmeaRepository $pfmeaRepo,
        protected DepartmentRepository $deptRepo,
        protected ChangeLogsService $logService
    ) {}

    public function getPfmeaList($filter, $per_page, $search = null)
    {
        try {
            return $this->pfmeaRepo->getPfmeaList($filter, $per_page, $search);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getDataById(int $id)
    {
        try {
            $header = $this->pfmeaRepo->getDataById($id);
            $core_teams = $this->pfmeaRepo->getCoreTeamByPfmeaId($id);
            $details = $this->pfmeaRepo->getDetails($id);

            return [
                'header' => $header->toArray(),
                'core_teams' => $core_teams->toArray(),
                'details' => $details->toArray()
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function store(array $data)
    {
        try {
            $user_id = Auth::id();
            $ip = Request::ip();
            $errors = [];
            $coreTeamCounts = collect($data['core_teams'])
                ->pluck('id')
                ->countBy();

            foreach ($data['core_teams'] as $index => $item) {

                if (($coreTeamCounts[$item['id']] ?? 0) > 1) {

                    $errors["core_teams.$index.id"] =
                        'Core team cannot be selected more than once.';
                }
            }

            $processCounts = collect($data['details'])
                ->pluck('process_id')
                ->countBy();

            foreach ($data['details'] as $index => $item) {

                if (($processCounts[$item['process_id']] ?? 0) > 1) {

                    $errors["details.$index.process_id"] =
                        'Process already exists.';
                }
            }

            if (!empty($errors)) {
                throw ValidationException::withMessages($errors);
            }

            // Get Department ID
            $department = $this->deptRepo->getDataByUUID($data['department_id']);
            if (!$department) {
                throw new \Exception('Provided department no available on system, please check and try again');
            }

            $dept_id = $department->id;

            $save = DB::transaction(function () use ($data, $user_id, $dept_id, $ip) {
                // Initialize pfmea data
                $data_pfmea = [
                    'code' => $data['code'],
                    'date' => trim($data['date']),
                    'department_id' => $dept_id,
                    'version' => 0,
                    'scope' => $data['scope'],
                    'project_id' =>  $data['project_id'],
                    'material_id' => $data['material_id'],
                    'process_responsibility' => $data['process_responsibility'],
                    'prepared_by' => $user_id,
                    'created_by' => $user_id
                ];

                // save pfmea header
                $save_header = $this->pfmeaRepo->store($data_pfmea);

                $data_core_team = collect($data['core_teams'])->map(function ($item, $index) use ($user_id) {
                    return [
                        'order' => $index + 1,
                        'user_id' => $item['id'],
                        'created_by' => $user_id,
                    ];
                })->toArray();

                $save_core_team = $this->pfmeaRepo->storeCoreTeam($save_header, $data_core_team);

                $data_details = collect($data['details'])->map(function ($item, $index) use ($user_id) {
                    return [
                        'order' => $index + 1,
                        'process_id' => $item['process_id'],
                        'created_by' => $user_id
                    ];
                })->toArray();

                $save_details = $this->pfmeaRepo->storeDetails($save_header, $data_details);

                $freshHeader = $this->pfmeaRepo->getDataById($save_header->id);
                $freshCoreTeams = $this->pfmeaRepo->getCoreTeamByPfmeaId($save_header->id);
                $freshDetails = $this->pfmeaRepo->getDetails($save_header->id);

                return [
                    'model' => $freshHeader,
                    'header' => $freshHeader ? $freshHeader->toArray() : [],
                    'core_teams' => $freshCoreTeams ? $freshCoreTeams->toArray() : [],
                    'details' => $freshDetails ? $freshDetails->toArray() : [],
                ];
            });

            $dataLogs = [
                'header' => $save['header'],
                'core_teams' => $save['core_teams'],
                'details' => $save['details']
            ];


            $this->logService->store($save['model'], 'create', 'Initialize new pfmea data', null, $dataLogs);
            activity('save_pfmea')
                ->causedBy($user_id)
                ->performedOn($save['model'])
                ->withProperties([
                    'header' => $save['header'],
                    'core_teams' => $save['core_teams'],
                    'details' => $save['details'],
                    'ip' => $ip
                ])
                ->Log('Save success: Successfully saved new PFMEA data');

            return $save['model'];
        } catch (ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                // Fallback jika error duplikat kode
                if (str_contains($e->getMessage(), 'code')) {
                    throw ValidationException::withMessages([
                        'code' => 'Code already exists.'
                    ]);
                }

                // Fallback jika error duplikat kombinasi Project & Material
                if (str_contains($e->getMessage(), 'material_id') || str_contains($e->getMessage(), 'project_id')) {
                    throw ValidationException::withMessages([
                        'material_id' => 'PFMEA document with this Project and Material combination already exists.'
                    ]);
                }
            }

            throw $e;
        } catch (\Throwable $e) {
            Log::error('Failed to save new PFMEA data with error : ' . $e->getMessage());
            activity('save_pfmea')
                ->causedBy($user_id)
                ->withProperties([
                    'input_data' => $data,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trance' => $e->getTraceAsString(),
                    'ip' => $ip,
                ])
                ->log('Save failed: failed save new pfmea data');

            throw $e;
        }
    }

    public function update(array $data, int $id)
    {
        try {
            $userId = Auth::id();
            $ip = Request::ip();

            $errors = [];
            $coreTeamCounts = collect($data['core_teams'])
                ->pluck('id')
                ->countBy();

            foreach ($data['core_teams'] as $index => $item) {
                if (($coreTeamCounts[$item['id']] ?? 0) > 1) {
                    $errors["core_teams.$index.id"] = 'Core team cannot be selected more than once.';
                }
            }

            $processCounts = collect($data['details'])
                ->pluck('process_id')
                ->countBy();

            foreach ($data['details'] as $index => $item) {
                if (($processCounts[$item['process_id']] ?? 0) > 1) {
                    $errors["details.$index.process_id"] = 'Process already exists.';
                }
            }

            if (!empty($errors)) {
                throw ValidationException::withMessages($errors);
            }

            // $department = $this->deptRepo->getDataByUUID($data['department_id']);
            // if (!$department) {
            //     throw new \Exception('Provided department not available on system, please check and try again');
            // }

            // $dept_id = $department->id;

            $update = DB::transaction(function () use ($data, $id, $userId) {
                $oldHeader = $this->pfmeaRepo->getDataById($id);
                $oldCoreTeams = $this->pfmeaRepo->getCoreTeamByPfmeaId($id);
                $oldDetails = $this->pfmeaRepo->getDetails($id);

                $dataHeader = [
                    'code' => $data['code'],
                    'date' => trim($data['date']),
                    'department_id' => trim($data['department_id']),
                    'version' => $oldHeader ? ($oldHeader->version + 1) : 1,
                    'scope' => $data['scope'],
                    'project_id' => $data['project_id'],
                    'material_id' => $data['material_id'],
                    'process_responsibility' => $data['process_responsibility'],
                    'updated_by' => $userId
                ];

                $updateHeader = $this->pfmeaRepo->updateHeader($dataHeader, $id);

                // Re-sync Core Teams (Delete Old & Insert New)
                $this->pfmeaRepo->deleteCoreTeamsByPfmeaId($id);
                $dataCoreTeams = collect($data['core_teams'])->map(function ($item, $index) use ($userId) {
                    return [
                        'order' => $index + 1,
                        'user_id' => $item['id'],
                        'created_by' => $userId,
                    ];
                })->toArray();
                $this->pfmeaRepo->storeCoreTeam($updateHeader, $dataCoreTeams);

                // Re-sync Details (Delete Old & Insert New)
                $this->pfmeaRepo->deleteDetailsByPfmeaId($id);
                $dataDetails = collect($data['details'])->map(function ($item, $index) use ($userId) {
                    return [
                        'order' => $index + 1,
                        'process_id' => $item['process_id'],
                        'created_by' => $userId
                    ];
                })->toArray();
                $this->pfmeaRepo->storeDetails($updateHeader, $dataDetails);

                $freshHeader = $this->pfmeaRepo->getDataById($id);
                $freshCoreTeams = $this->pfmeaRepo->getCoreTeamByPfmeaId($id);
                $freshDetails = $this->pfmeaRepo->getDetails($id);

                return [
                    'model' => $freshHeader,
                    'old_data' => [
                        'header' => $oldHeader ? $oldHeader->toArray() : [],
                        'core_teams' => $oldCoreTeams ? $oldCoreTeams->toArray() : [],
                        'details' => $oldDetails ? $oldDetails->toArray() : []
                    ],
                    'new_data' => [
                        'header' => $freshHeader ? $freshHeader->toArray() : [],
                        'core_teams' => $freshCoreTeams ? $freshCoreTeams->toArray() : [],
                        'details' => $freshDetails ? $freshDetails->toArray() : []
                    ]
                ];
            });

            activity('update_pfmea')
                ->causedBy($userId)
                ->performedOn($update['model'])
                ->withProperties([
                    'ip' => $ip,
                    'old' => $update['old_data'],
                    'new' => $update['new_data']
                ])
                ->log('Update success: Successfully updated PFMEA data');

            $reason = $data['reason'] ?? 'Update PFMEA document';
            $this->logService->store($update['model'], 'update', trim($reason), $update['old_data'], $update['new_data']);

            return $update['model'];
        } catch (ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                // Fallback jika error duplikat kode
                if (str_contains($e->getMessage(), 'code')) {
                    throw ValidationException::withMessages([
                        'code' => 'Code already exists.'
                    ]);
                }

                // Fallback jika error duplikat kombinasi Project & Material
                if (str_contains($e->getMessage(), 'material_id') || str_contains($e->getMessage(), 'project_id')) {
                    throw ValidationException::withMessages([
                        'material_id' => 'PFMEA document with this Project and Material combination already exists.'
                    ]);
                }
            }

            throw $e;
        } catch (\Throwable $e) {
            Log::error('Failed to update PFMEA data with error : ' . $e->getMessage());
            activity('update_pfmea')
                ->causedBy($userId)
                ->withProperties([
                    'input_data' => $data,
                    'message' => $e->getMessage(),
                    'ip' => $ip,
                ])
                ->log('Update failed: failed update pfmea data');

            throw $e;
        }
    }

    public function delete(int $id) {}

    public function massDelete(array $ids) {}

    public function getLogs($id)
    {
        try {
            return $this->pfmeaRepo->getLogs($id);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function getPfmeaData($projectId, $materialId)
    {
        try {
            $pfmea = $this->pfmeaRepo->getDataList($projectId, $materialId);

            if (!$pfmea) {
                return [
                    'success' => false,
                    'code'    => 404,
                    'message' => 'Data PFMEA tidak ditemukan untuk project & material ini.',
                    'data'    => null
                ];
            }

            // Ambil daftar nama core team yang aktif / tidak null
            $coreTeamNames = $pfmea->coreTeam
                ->map(fn($item) => $item->team?->name)
                ->filter()
                ->values()
                ->toArray();

            $revisions = DB::table('change_logs_data')
                ->where('item_id', $pfmea->id)
                ->where('table_name', 'pfmea')
                ->orderBy('created_at', 'asc') // Urutkan dari revisi awal ke terbaru
                ->get()
                ->map(function ($log) {
                    return [
                        'ver'      => $log->revision ?? '-',      // Sesuaikan nama kolom versi di change_logs_data
                        'date'     => $log->created_at ?? '-',   // Sesuaikan kolom tanggal
                        'content'  => $log->change_reason ?? $log->change_reason ?? '-', // Sesuaikan kolom deskripsi/konten
                        'before'    => $log->before,
                        'after'     => $log->after,
                    ];
                })
                ->toArray();

            return [
                'success' => true,
                'code'    => 200,
                'message' => 'Data PFMEA berhasil ditemukan.',
                'data'    => [
                    'id'                     => $pfmea->id,
                    'doc_no'                 => $pfmea->code,
                    'issue_date'             => $pfmea->date ? $pfmea->date->format('Y-m-d') : '-',
                    'issuing_dept'           => $pfmea->department?->name ?? '-',
                    'page'                   => "1 of {$pfmea->version}",
                    'key_date'               => $pfmea->date ? $pfmea->date->format('Y-m-d') : '-',
                    'part_name'              => $pfmea->material?->name ?? '-',
                    'part_no'                => $pfmea->material?->code ?? '-',
                    'customer_part_name'     => $pfmea->material?->customer_part_name ?? '-',
                    'process_responsibility' => $pfmea->process_responsibility,
                    'dwg_no'                 => $pfmea->material?->drawing_change,
                    'scope'                  => $pfmea->scope,
                    'core_team'              => !empty($coreTeamNames) ? implode(', ', $coreTeamNames) : '-',
                    'prepared_by'            => $pfmea->creator?->name ?? '-',
                    'reviewed_by'            => $pfmea->reviewed_by ?? '-',
                    'approved_by'            => $pfmea->approved_by ?? '-',

                    'revision_history'       => $revisions,
                    // Detail items diratakan pake flatMap menembus process_function_details
                    'items'                  => $pfmea->details->flatMap(function ($detail) {
                        $processFunction = $detail->processFunction;
                        $subDetails = $processFunction?->details ?? collect();

                        // Jika proses memiliki detail baris di tabel process_function_details
                        if ($subDetails->isNotEmpty()) {
                            return $subDetails->map(function ($sub) use ($processFunction, $detail) {
                                return [
                                    'id'                     => "{$detail->id}-{$sub->id}",
                                    'order'                  => $detail->order,
                                    'process_id'             => $detail->process_id,
                                    'process_step'           => $processFunction?->name ?? '-',
                                    'revision'               => $processFunction?->revision ?? 0,
                                    'remark'                 => $processFunction?->remark ?? '',
                                    // Ambil kolom dari process_function_details (sesuaikan nama kolomnya kalau ada beda)
                                    'kakotora_yc'            => $sub->previous_problem ?? '-',
                                    'requirements'           => $sub->requirements ?? '-',
                                    'potential_failure_mode' => $sub->potential_failure_mode ?? '-',
                                    'potential_effects'      => $sub->potential_effect_of_failure ?? '-',
                                    'classification'         => $sub->classification ?? '-',
                                    'occurrence'             => $sub->occurrence ?? null,
                                    'detection'              => $sub->detection ?? null,
                                    'rpn'                    => $sub->rpn ?? null,
                                    'recommended_actions'    => $sub->recommended_action ?? '-',
                                    'severity'               => $sub->severity ?? null,
                                    'responsibility'         => $sub->responsibility ?? '-',
                                    'potential_causes'       => $sub->potential_cause_of_failure ?? '-',
                                    'controls_prevention'    => $sub->controls_prevention ?? '-',
                                    'control_detection'      => $sub->controls_detection ?? '-',
                                    'responsibility'         => $sub->responsibility ?? '-',
                                    'target_completion_date' => $sub->target_completion_date ?? '-',
                                    'action_taken_date'      => $sub->action_taken_date ?? '-',
                                    'result_severity'        => $sub->result_severity ?? null,
                                    'result_occurrence'      => $sub->result_occurrence ?? null,
                                    'result_detection'       => $sub->result_detection ?? null,
                                    'result_rpn'             => $sub->result_rpn ?? null,
                                ];
                            });
                        }

                        // Fallback jika process_function belum punya detail baris di process_function_details
                        return [[
                            'id'                     => $detail->id,
                            'order'                  => $detail->order,
                            'process_id'             => $detail->process_id,
                            'process_step'           => $processFunction?->name ?? '-',
                            'revision'               => $processFunction?->revision ?? 0,
                            'remark'                 => $processFunction?->remark ?? '',
                            'kakotora_yc'            => '-',
                            'requirements'           => '-',
                            'potential_failure_mode' => '-',
                            'potential_effects'      => '-',
                            'severity'               => null,
                            'classification'         => '-',
                            'potential_causes'       => '-',
                            'occurrence'             => null,
                            'controls_prevention'    => '-',
                            'control_detection'      => '-',
                            'detection'              => null,
                            'rpn'                    => null,
                            'recommended_actions'    => '-',
                            'responsibility'         => '-',
                            'target_completion_date' => '-',
                            'action_taken_date'      => '-',
                            'result_severity'        => null,
                            'result_occurrence'      => null,
                            'result_detection'       => null,
                            'result_rpn'             => null,
                        ]];
                    })->values()->toArray(),
                ]
            ];
        } catch (\Exception $e) {
            Log::error("PfmeaService Error: " . $e->getMessage());
            return [
                'success' => false,
                'code'    => 500,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }
}
