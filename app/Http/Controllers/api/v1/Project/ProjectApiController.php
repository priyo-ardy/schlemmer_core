<?php

namespace App\Http\Controllers\api\v1\Project;

use App\Http\Controllers\Controller;
use App\Services\MaterialService\MaterialService;
use App\Services\Project\ProjectService;
use Illuminate\Http\Request;

class ProjectApiController extends Controller
{
    public function __construct(
        protected ProjectService $projectService,
        protected MaterialService $materialService
    ) {}

    public function dropdown(Request $request)
    {
        $search = $request->query('search');

        $projects = $this->projectService->searchProject($search);

        return response()->json([
            'data' => $projects->items(),
            'has_more' => $projects->hasMorePages()
        ]);
    }

    public function getMaterialList(Request $request, $project_id)
    {
        $search = $request->query('search');
        $materials = $this->materialService->getListByProject($search, $project_id);

        return response()->json([
            'data' => $materials->items(),
            'has_more' => $materials->hasMorePages()
        ]);
    }
}
