<?php

namespace App\Http\Controllers\Pfmea;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Services\Department\DepartmentService;
use App\Services\MaterialService\MaterialService;
use App\Services\PFMEA\PfmeaService;
use App\Services\ProcessTemplate\ProcessTemplateServices;
use App\Services\Users\UserServices;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PfmeaController extends Controller
{
    public function __construct(
        protected PfmeaService $pfmeaService,
        protected UserServices $userService,
        protected MaterialService $materialService,
        protected DepartmentService $deptService,
        protected ProcessTemplateServices $processService,
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('Pfmea/Index', [
            'page_title' => 'PFMEA List'
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Pfmea/Create', [
            'users' => $this->userService->getAllUsers(),
            // 'materials' => $this->materialService->getMaterialList('all', 10, null),
            'departments' => DepartmentResource::collection($this->deptService->getAllData()),
            'page_title' => 'Create PFMEA Document'
        ]);
    }
}
