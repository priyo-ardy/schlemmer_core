<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\PfmeaHeader;
use App\Services\PFMEA\PfmeaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PfmeaApiController extends Controller
{
    public function __construct(
        protected PfmeaService $pfmeaService
    ) {}

    public function dataList(Request $request)
    {
        $projectId  = $request->query('project_id');
        $materialId = $request->query('material_id');

        if (!$projectId || !$materialId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Parameter Project dan Material wajib dipilih!'
            ], 400); // 400 Bad Request
        }

        $result = $this->pfmeaService->getPfmeaData($projectId, $materialId);

        if (!$result['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message']
            ], $result['code']);
        }

        return response()->json([
            'status'  => 'success',
            'message' => $result['message'],
            'data'    => $result['data']
        ], $result['code']);
    }
}
