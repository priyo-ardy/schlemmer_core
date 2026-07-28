<?php

namespace App\Repositories\ApprovalSetup;

use App\Models\ApprovalSetup;

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

    public function getDataById(int $id) {}

    public function getDataByUuid(string $uuid) {}

    public function storeData(array $data) {}

    public function updateData(int $id, array $data) {}

    public function massDelete(array $ids) {}
}
