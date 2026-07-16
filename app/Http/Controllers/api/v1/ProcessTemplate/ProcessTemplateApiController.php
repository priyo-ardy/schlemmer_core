<?php

namespace App\Http\Controllers\api\v1\ProcessTemplate;

use App\Http\Controllers\Controller;
use App\Services\ProcessTemplate\ProcessTemplateServices;
use Illuminate\Http\Request;

class ProcessTemplateApiController extends Controller
{
    public function __construct(
        protected ProcessTemplateServices $processService
    ) {}

    public function dropdown(Request $request)
    {
        try {
            $search = $request->query('search');

            $process = $this->processService->searchProcess($search);

            return response()->json([
                'data' => $process->items(),
                'has_more' => $process->hasMorePages()
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('errors', "Internal server error: " . $e->getMessage());
        }
    }
}
