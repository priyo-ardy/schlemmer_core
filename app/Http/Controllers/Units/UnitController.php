<?php

namespace App\Http\Controllers\Units;

use App\Http\Controllers\Controller;
use App\Services\UnitCategory\UnitCategoryService;
use App\Services\Units\UnitService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UnitController extends Controller
{
    public function __construct(
        protected UnitService $unitService,
        protected UnitCategoryService $categoryService
    ) {}

    public function index()
    {
        return Inertia::render('Units/Unit', [
            'categories' =>  $this->categoryService->getAllData(),
            'units' => $this->unitService->getAllData(),
            'page_title' => 'Master Data / UoM Management'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string|max:10|unique:units,symbol',
            'name' => 'required|string|max:150',
            'is_active' => 'required|boolean',
            'remark' => 'nullable|string|max:255',
            'category_id' => 'required|integer|exists:unit_categories,id',
            'is_base_unit' => 'nullable|boolean',
            'conversion_factor' => 'nullable|numeric',
            'conversion_offset' => 'nullable|numeric',
            'decimal_places' => 'nullable|integer|min:0',
        ]);

        $this->unitService->store($validated);

        return redirect()->back()->with('success', 'New uom data has been stored successfully');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'symbol' => [
                'required',
                'string',
                'max:10',
                Rule::unique('units', 'symbol')->ignore($id),
            ],
            'name' => 'required|string|max:150',
            'is_active' => 'required|boolean',
            'remark' => 'nullable|string|max:255',
            'category_id' => 'required|integer|exists:unit_categories,id',
            'is_base_unit' => 'nullable|boolean',
            'conversion_factor' => 'nullable|numeric',
            'conversion_offset' => 'nullable|numeric',
            'decimal_places' => 'nullable|integer|min:0',
            'reason' => 'required|string'
        ]);

        $this->unitService->update($id, $validated);

        return redirect()->back()->with('success', 'UoM data updated successfully');
    }

    public function massDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:units,id',
            'remark' => 'required|string'
        ]);

        $this->unitService->massDelete($request->ids, $request->input('remark'));

        return redirect()->back()->with('success', 'Data deleted successfully');
    }

    public function getLog(Request $request, int $id)
    {
        try {
            $logs = $this->unitService->getLogsData($id);

            return response()->json($logs);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'bulk_error' => $e->getMessage()
            ]);
        }
    }
}
