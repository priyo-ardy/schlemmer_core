<?php

namespace App\Services\ProcessTemplate;

use App\Repositories\ProcessTemplate\ProcessTemplateRepository;
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

    public function __construct(ProcessTemplateRepository $processRepo, ProcessRevisionService $revisionService)
    {
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
                    'remark' => !empty($data['remark']) ? trim($data['remark']) : null,
                    'is_active' => true,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ];

                // Insert ke teble header
                $processFunction = $this->processRepo->storeHeader($headerData);

                activity('save_process_function_header')
                    ->causedBy($userId)
                    ->withProperties([
                        'data' => $headerData,
                        'ip' => Request::ip()
                    ])
                    ->log('Save success: Successfully saved process function header data');

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
                activity('save_process_function_details')
                    ->causedBy($userId)
                    ->withProperties([
                        'data' => $detailsData,
                        'ip' => Request::ip()
                    ])
                    ->log('Save success: Successfully saved process function details data');

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
}
