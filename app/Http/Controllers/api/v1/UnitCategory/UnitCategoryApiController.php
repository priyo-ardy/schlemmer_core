<?php

namespace App\Http\Controllers\api\v1\UnitCategory;

use App\Http\Controllers\Controller;
use App\Http\Resources\UnitCategoriesResources;
use App\Services\UnitCategory\UnitCategoryService;
use Illuminate\Http\Request;

class UnitCategoryApiController extends Controller
{
    public function __construct(
        protected UnitCategoryService $categoryService
    ) {}

    public function list(Request $request)
    {
        $search = $request->query('search');

        $categories = $this->categoryService->getList($search);

        return response()->json($categories);
    }
}
