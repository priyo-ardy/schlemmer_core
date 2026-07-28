<?php

namespace App\Services\ApprovalSetup;

use App\Repositories\ApprovalSetup\ApprovalSetupRepository;
use App\Services\ChangeLogs\ChangeLogsService;

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

    public function store(array $data) {}

    public function update(int $id, array $data) {}

    public function massDelete(array $ids) {}
}
