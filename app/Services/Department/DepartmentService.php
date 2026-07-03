<?php

namespace App\Services\Department;

use App\Services\ChangeLogs\ChangeLogsService;

class DepartmentService
{
    public function __construct(
        protected DepartmentService $deptService,
        protected ChangeLogsService $logService
    ) {}

    public function getAllData()
    {
        try {
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
