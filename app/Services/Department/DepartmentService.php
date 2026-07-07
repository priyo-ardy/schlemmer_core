<?php

namespace App\Services\Department;

use App\Models\Department;
use App\Repositories\Department\DepartmentRepository;
use App\Services\ChangeLogs\ChangeLogsService;
use Illuminate\Database\Eloquent\Collection;

class DepartmentService
{
    public function __construct(
        protected DepartmentRepository $deptRepo,
        protected ChangeLogsService $logService
    ) {}

    public function getAllData(): Collection
    {
        try {
            return Department::orderBy('name', 'asc')->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function deptSearch($search): Collection
    {
        $dept = Department::query()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'LIKE', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'name']);
        return $dept;
    }
}
