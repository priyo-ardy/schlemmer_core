<?php

namespace App\Http\Controllers\api\v1\Units;

use App\Http\Controllers\Controller;
use App\Services\Units\UnitService;
use Illuminate\Http\Request;

class UnitApiController extends Controller
{
    public function __construct(
        protected UnitService $unitService
    ) {}

    public function list(Request $request)
    {
        $search = $request->query('search');

        return $this->unitService->getLists($search);
    }
}
