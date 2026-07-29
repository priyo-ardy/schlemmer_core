<?php

namespace App\Services\ApprovalSetup;

use App\Models\ApprovalSetup;
use App\Repositories\ApprovalSetup\ApprovalSetupRepository;
use App\Services\ChangeLogs\ChangeLogsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class ApprovalSetupService
{
    public function __construct(
        protected ApprovalSetupRepository $approvalRepo,
        protected ChangeLogsService $logService
    ) {}

    public function getAllData($filter, $page, $search = null)
    {
        return $this->approvalRepo->getAll($filter, $page, $search);
    }

    public function store(array $data)
    {
        $user_id = Auth::id();
        $ip_address = Request::ip();

        try {
            $save = DB::transaction(function () use ($data, $user_id, $ip_address) {
                $dataHeader = [
                    'revision' => 0,
                    'module' => trim($data['module']),
                    'is_active' => true,
                    'remark' => isset($data['remark']) ? trim($data['remark']) : null,
                ];

                $saveHeader = $this->approvalRepo->storeHeader($dataHeader);

                $approverList = collect($data['approver'])
                    ->map(function ($item, $index) {
                        return [
                            'order' => $index + 1,
                            'approver_id' => is_array($item) ? $item['approver_id'] : $item,
                        ];
                    })->toArray();

                $saveDetails = $this->approvalRepo->storeDetail($saveHeader, $approverList);

                return [
                    'model' => $saveHeader,
                    'header' => $saveHeader->toArray(),
                    'details' => $saveDetails->toArray(),
                ];
            });

            $dataLogs = [
                'header' => $save['header'],
                'details' => $save['details']
            ];

            $this->logService->store($save['model'], 'create', 'Initialize new approval setup', null, $dataLogs);

            return $save['model'];
        } catch (\Exception $e) {
            Log::error('Failed to save approval setup data with error : ' . $e->getMessage());

            activity('save_approval_setup')
                ->causedBy($user_id)
                ->withProperties([
                    'input_data' => $data,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => $ip_address,
                ])
                ->log('Save failed: failed to save new approval setup data');

            throw $e;
        }
    }

    public function getAll()
    {
        return ApprovalSetup::orderBy('module', 'asc')->pluck('id');
    }

    public function getData($id)
    {
        return $this->approvalRepo->getHeaderById($id);
    }

    public function update(array $data, int $id)
    {
        $userId = Auth::id();
        $ipAddress = request()->ip();

        try {
            $update = DB::transaction(function () use ($data, $id) {
                $oldHeader = $this->approvalRepo->getHeaderById($id);

                // 1. Fail-early jika header data tidak ditemukan
                if (!$oldHeader) {
                    throw new \Exception("Approval setup header with ID {$id} not found.");
                }

                $oldDetail = $this->approvalRepo->getDetailByHeader($id);

                $headerData = [
                    'revision' => $oldHeader->revision + 1,
                    'module'   => $data['module'],
                    'remark'   => !empty($data['remark']) ? trim($data['remark']) : null, // Safely handle null
                ];

                $freshHeader = $this->approvalRepo->updateHeader($headerData, $id);

                $incomingApprovers = collect($data['approver'] ?? []);

                // 2. Ambil ID primary key detail untuk filter delete
                $incomingIds = $incomingApprovers->pluck('id')->filter()->toArray();
                $this->approvalRepo->deleteDetailsNotIn($id, $incomingIds);

                // 3. Masukkan 'id' dan 'approval_setup_id' agar upsert berfungsi presisi
                $approverToSave = $incomingApprovers->map(function ($item, $index) use ($id) {
                    return [
                        'id'                => $item['id'] ?? null,
                        'uuid'              => (string) Str::uuid7(),
                        'header_id'         => $id,
                        'order'             => $index + 1,
                        'approver_id'       => $item['approver_id'],
                        'updated_by'        => Auth::id()
                    ];
                })->toArray();

                if (!empty($approverToSave)) {
                    $this->approvalRepo->upsertDetails($approverToSave);
                }

                $freshDetail = $this->approvalRepo->getDetailByHeader($id);

                return [
                    'model'       => $freshHeader,
                    'old_header'  => $oldHeader->toArray(),
                    'old_details' => $oldDetail ? $oldDetail->toArray() : [],
                    'header'      => $freshHeader ? $freshHeader->toArray() : [],
                    'details'     => $freshDetail ? $freshDetail->toArray() : [],
                ];
            });

            $oldData = [
                'header'  => $update['old_header'],
                'details' => $update['old_details'],
            ];

            $newData = [
                'header'  => $update['header'],
                'details' => $update['details'],
            ];

            $this->logService->store($update['model'], 'update', trim($data['reason'] ?? ''), $oldData, $newData);

            activity('update_approval_setup')
                ->causedBy($userId)
                ->performedOn($update['model'])
                ->withProperties([])
                ->log('Update success: Successfully update approval setup data');

            return $update['model'];
        } catch (\Exception $e) {
            Log::error("Failed to update approval setup ID {$id} with error: " . $e->getMessage());

            activity('update_approval_setup') // Nama disamakan dengan block try
                ->causedBy($userId)
                ->withProperties([
                    'input_id'   => $id,
                    'input_data' => $data,
                    'message'    => $e->getMessage(),
                    'file'       => $e->getFile(),
                    'line'       => $e->getLine(),
                    'trace'      => $e->getTraceAsString(),
                    'ip'         => $ipAddress,
                ])
                ->log('Update failed: failed to update approval setup data');

            throw $e;
        }
    }

    public function massDelete(array $ids) {}
}
