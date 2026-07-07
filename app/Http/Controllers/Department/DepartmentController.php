<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Services\Department\DepartmentService;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentService $deptService
    ) {}

    public function dropDownSearch(Request $request)
    {
        $search = $request->query('search');

        $departments = DepartmentResource::collection($this->deptService->deptSearch($search));

        return response()->json($departments);
    }
}
