<?php

namespace App\Http\Controllers\Material;

use App\Http\Controllers\Controller;
use App\Services\MaterialService\MaterialService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaterialController extends Controller
{
    protected MaterialService $materialService;

    public function __construct(MaterialService $materialService)
    {
        $this->materialService = $materialService;
    }

    public function index(Request $request)
    {
        $materials = $this->materialService->getMaterialList(
            $request->input('per_page', 10),
            $request->input('search')
        );

        return Inertia::render('Material/list', [
            'materials' => $materials
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Material/create');
    }
}
