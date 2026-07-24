<?php

namespace App\Repositories\Department;

use App\Models\Department;

class DepartmentRepository
{
    public function getAllData(): Department
    {
        return Department::orderBy('code', 'asc')->get();
    }

    public function getDataById($id)
    {
        return Department::find($id);
    }

    public function getDataByUUID($uuid)
    {
        return Department::where('uuid', $uuid)->first();
    }
}
