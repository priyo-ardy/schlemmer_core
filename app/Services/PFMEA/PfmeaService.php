<?php

namespace App\Services\PFMEA;

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
        protected ChangeLogsService $logService
    ) {}

    public function searchData($search) {}

    public function getDataList() {}

    public function getDataById(int $id) {}

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

            $save = DB::transaction(function () use ($data, $user_id) {
                // Initialize pfmea data
                $data_pfmea = [
                    'code' => $data['code'],
                    'date' => trim($data['date']),
                    'department_id' => $data['department_id'],
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

                return [
                    'model' => $save_header,
                    'header' => $save_header->toArray(),
                    'core_teams' => $save_core_team->toArray(),
                    'details' => $save_details->toArray(),
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
            if (
                ($e->errorInfo[1] ?? null) === 1062 &&
                str_contains($e->getMessage(), 'pfmea_code_unique')
            ) {
                throw ValidationException::withMessages([
                    'code' => 'Code already exists.'
                ]);
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

    public function update(array $data, int $id) {}

    public function delete(int $id) {}

    public function massDelete(array $ids) {}
}
