<?php

namespace App\Repositories\ApprovalSetup;

use App\Models\ApprovalSetup;
use App\Models\ApprovalSetupDetail;

class ApprovalSetupRepository
{
    public function getAll($filter, $page, $search = null)
    {
        $query = ApprovalSetup::with(['details.approver', 'creator', 'updater'])
            ->orderBy('module', 'asc');

        if ($filter && $filter !== 'all') {
            $query->where('is_active', $filter === 'enable' ? 1 : 0);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('module', 'LIKE', "%{$search}%")
                    ->orWhere('remark', 'LIKE', "%{$search}%")
                    ->orWhereHas('details.approver', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        return $query->paginate($page)->withQueryString();
    }

    public function getHeaderById(int $id): ApprovalSetup
    {
        return ApprovalSetup::with([
            'details.approver', // Eager load relasi approver di dalam detail
            'creator',
            'updater'
        ])->findOrFail($id);
    }

    public function getDetailByHeader(int $id)
    {
        return ApprovalSetupDetail::where('header_id', $id)->get();
    }

    public function getDataByUuid(string $uuid) {}

    public function storeHeader(array $data): ApprovalSetup
    {
        return ApprovalSetup::create($data);
    }

    public function storeDetail(ApprovalSetup $approvalSetup, array $approverLists)
    {
        return $approvalSetup->details()->createMany($approverLists);
    }

    public function updateHeader(array $data, int $id)
    {
        $header = ApprovalSetup::findOrFail($id);
        if (!$header) {
            return;
        }

        $header->update($data);

        return $header;
    }

    public function deleteDetailsNotIn(int $idHeader, array $Ids)
    {
        return ApprovalSetupDetail::where('header_id', $idHeader)
            ->whereNotIn('id', $Ids)
            ->delete();
    }

    public function upsertDetails(array $approvers)
    {
        if (empty($approvers)) return;

        return ApprovalSetupDetail::upsert($approvers, ['id'], [
            'uuid',
            'order',
            'approver_id',
            'updated_at',
            'updated_by'
        ]);
    }

    public function massDelete(array $ids) {}
}
