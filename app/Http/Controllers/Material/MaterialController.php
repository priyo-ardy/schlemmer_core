<?php

namespace App\Http\Controllers\Material;

use App\Http\Controllers\Controller;
use App\Services\MaterialService\MaterialService;
use App\Services\Units\UnitService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MaterialController extends Controller
{
    protected MaterialService $materialService;

    public function __construct(
        MaterialService $materialService,
        protected UnitService $unitService
    ) {
        $this->materialService = $materialService;
    }

    public function index(Request $request)
    {
        $units = $this->unitService->getAllData();

        return Inertia::render('Material/Material', [
            'materials' => $this->materialService->getMaterialList(
                $request->input('filter'),
                $request->input('per_page', 10),
                $request->input('search'),
            ),
            'units' => $units,
            'page_title' => 'Master Data / Material Management'
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'category' => 'required|string',
                'code' => 'required|string|max:50|unique:materials,code',
                'name' => 'required|string|max:150',
                'specification' => 'required|string|max:255',
                'customer_part_name' => 'nullable|string|max:255',
                'unit_id' => 'required|integer',
                'grade' => 'nullable|string|max:150',
                'density' => 'nullable|numeric|between:0,9999.9999|decimal:0,4',
                'melt_flow_index' => 'nullable|numeric|between:0,9999.9999|decimal:0,4',
                'color' => 'nullable|string|max:50',
                'drawing_change' => 'nullable|string|max:50',
                'shrinkage_rate' => 'nullable|string|max:50',
                'gross_weight' => 'nullable|numeric|between:0,9999.9999|decimal:0,4',
                'net_weight' => 'nullable|numeric|between:0,9999.9999|decimal:0,4',
                'sprue_weight' => 'nullable|numeric|between:0,9999.9999|decimal:0,4',
                'has_rohs' => 'nullable|boolean',
                'imds_number' => 'nullable|string|max:50',
                'msds_doc_path' => 'nullable|string|max:150',
                'risk_profile' => 'nullable|string|max:10',
                'is_active' => 'required|boolean',
                'remark' => 'nullable|string',
            ]);

            $this->materialService->store($validated);

            return redirect()->back()->with('success', 'Successfully stored new material data');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('materials', 'code')->ignore($id)
                ],
                'category' => 'required|string',
                'name' => 'required|string|max:150',
                'specification' => 'required|string|max:255',
                'customer_part_name' => 'nullable|string|max:255',
                'unit_id' => 'required|integer',
                'grade' => 'nullable|string|max:150',
                'density' => 'nullable|numeric|between:0,9999.9999|decimal:0,4',
                'melt_flow_index' => 'nullable|numeric|between:0,9999.9999|decimal:0,4',
                'color' => 'nullable|string|max:50',
                'drawing_change' => 'nullable|string|max:50',
                'shrinkage_rate' => 'nullable|string|max:50',
                'gross_weight' => 'nullable|numeric|between:0,9999.9999|decimal:0,4',
                'net_weight' => 'nullable|numeric|between:0,9999.9999|decimal:0,4',
                'sprue_weight' => 'nullable|numeric|between:0,9999.9999|decimal:0,4',
                'has_rohs' => 'nullable|boolean',
                'imds_number' => 'nullable|string|max:50',
                'msds_doc_path' => 'nullable|string|max:150',
                'risk_profile' => 'nullable|string|max:10',
                'is_active' => 'required|boolean',
                'remark' => 'nullable|string',
                'reason' => 'required|string'
            ]);

            $this->materialService->update($id, $validated);

            return redirect()->back()->with('success', 'Successfully updated material data');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function massDelete(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:materials,id',
                'remark' => 'required'
            ]);

            $this->materialService->bulkDelete($validated);

            return redirect()->back()->with('success', 'Successfully deleted material data');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function getLog(Request $request, int $id)
    {
        try {
            $logs = $this->materialService->getLogsData($id);

            return response()->json($logs);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'bulk_error' => $e->getMessage()
            ]);
        }
    }
}
