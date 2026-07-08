<?php

namespace App\Http\Controllers\api\v1\Material;

use App\Http\Controllers\Controller;
use App\Http\Resources\Material\MaterialResource;
use App\Services\MaterialService\MaterialService;
use Illuminate\Http\Request;

class MaterialApiController extends Controller
{
    public function __construct(
        protected MaterialService $materialService
    ) {}

    public function list(Request $request)
    {
        $materials =  $this->materialService->getAllData();
        return response()->json($materials);
    }

    public function dropdown(Request $request)
    {
        $search = $request->query('search');

        $materials = $this->materialService->searchMaterial($search);

        return response()->json([
            'data'     => MaterialResource::collection($materials->items()),
            'has_more' => $materials->hasMorePages(),
        ]);
    }
}
