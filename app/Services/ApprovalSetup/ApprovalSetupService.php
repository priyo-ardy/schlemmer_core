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
        $user = Auth::user();
        $ipAddress = request()->ip();

        try {
            $update = DB::transaction(function () use ($data, $id) {
                $oldHeader = $this->approvalRepo->getHeaderById($id);

                if (!$oldHeader) {
                    throw new \Exception("Approval setup header with ID {$id} not found.");
                }

                $oldDetail = $this->approvalRepo->getDetailByHeader($id);

                $headerData = [
                    'revision' => $oldHeader ? $oldHeader->revision + 1 : 1,
                    'module'   => $data['module'],
                    'remark'   => !empty($data['remark']) ? trim($data['remark']) : null, // Safely handle null
                ];

                $freshHeader = $this->approvalRepo->updateHeader($headerData, $id);

                $incomingApprovers = collect($data['approver'] ?? []);
                $incomingIds = $incomingApprovers->pluck('id')->filter()->toArray();
                $this->approvalRepo->deleteDetailsNotIn($id, $incomingIds);

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
                ->causedBy($user) // Menggunakan Model User
                ->performedOn($update['model'])
                ->withProperties([
                    'reason'        => trim($data['reason'] ?? ''),
                    'old_data'      => $oldData,
                    'new_data'      => $newData,
                    'ip'            => $ipAddress,
                ])
                ->log('Update success: Successfully update approval setup data');

            return $update['model'];
        } catch (\Exception $e) {
            Log::error("Failed to update approval setup ID {$id} with error: " . $e->getMessage());

            activity('update_approval_setup') // Nama disamakan dengan block try
                ->causedBy($user)
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

    public function getLogs(int $id)
    {
        return $this->approvalRepo->getLogs($id);
    }

    public function delete(array $data)
    {
        $user = Auth::user();
        $ipAddress = request()->ip();

        // 1. Dukung payload array 'ids' (Mass Delete) maupun single 'id'
        $ids = $data['ids'] ?? (isset($data['id']) ? [$data['id']] : []);
        $reason = trim($data['reason'] ?? $data['remark'] ?? '');

        if (empty($ids)) {
            throw new \Exception('No deleted data provided.');
        }

        try {
            return DB::transaction(function () use ($ids, $reason, $user, $ipAddress, $data) {
                $deletedCount = 0;

                foreach ($ids as $id) {
                    // 2. Ambil data lama (Header + Detail) sebelum di-delete
                    $oldHeader = $this->approvalRepo->getHeaderById($id);

                    if (!$oldHeader) {
                        continue;
                    }

                    $oldData = [
                        'header'  => $oldHeader->toArray(),
                        'details' => $oldHeader->details ? $oldHeader->details->toArray() : [],
                    ];

                    // 3. Simpan ke Audit Log Service internal (merekam snapshot 'before' & 'reason')
                    $this->logService->store($oldHeader, 'delete', $reason, $oldData, null);

                    // 4. Activity Log Spatie untuk tiap item yang berhasil di-delete
                    activity('delete_approval_setup')
                        ->causedBy($user)
                        ->performedOn($oldHeader)
                        ->withProperties([
                            'reason' => $reason,
                            'old'    => $oldData,
                            'ip'     => $ipAddress,
                        ])
                        ->log("Delete success: Successfully deleted approval setup ID {$id}");

                    // 5. Eksekusi hapus di Repository
                    $this->approvalRepo->delete($id);
                    $deletedCount++;
                }

                return $deletedCount;
            });
        } catch (\Exception $e) {
            Log::error("Failed to delete approval setup with error: " . $e->getMessage());

            activity('delete_approval_setup')
                ->causedBy($user)
                ->withProperties([
                    'input_ids'  => $ids,
                    'input_data' => $data,
                    'message'    => $e->getMessage(),
                    'file'       => $e->getFile(),
                    'line'       => $e->getLine(),
                    'trace'      => $e->getTraceAsString(),
                    'ip'         => $ipAddress,
                ])
                ->log('Delete failed: failed to perform delete action');

            throw $e;
        }
    }

    public function massDelete(array $data)
    {
        $user = Auth::user();
        $ip_address = Request::ip();

        try {
            $delete = DB::transaction(function () use ($data, $user, $ip_address) {
                if (empty($data)) {
                    throw new \Exception('Mass delete failed, no approval setup data proovided');
                }

                $approval = $this->approvalRepo->findManyIds($data['ids']);

                if ($approval->isEmpty()) {
                    throw new \Exception('No data found from provided approval setup data');
                }

                foreach ($approval as $row) {
                    $this->logService->store($row, 'delete', trim($data['reason']), $approval->toArray(), null);
                    activity('mass_delete_approval_setup')
                        ->causedBy($user)
                        ->performedOn($row)
                        ->withProperties([
                            'input_id' => $row->id,
                            'ip' => $ip_address
                        ])
                        ->log('Delete success: Successfully deleted approval setup data');
                }

                $this->approvalRepo->massDelete($data['ids']);
            });

            return $delete;
        } catch (\Exception $e) {
            activity('mass_delete_approver_setup')
                ->causedBy($user)
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
}
