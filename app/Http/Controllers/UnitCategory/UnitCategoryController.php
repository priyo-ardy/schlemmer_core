<?php

namespace App\Http\Controllers\UnitCategory;

use App\Http\Controllers\Controller;
use App\Services\UnitCategory\UnitCategoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnitCategoryController extends Controller
{
    public function __construct(
        protected UnitCategoryService $categoryService
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('Units/Category', [
            'page_title' => 'Master Data / Unit of Measure / UoM Categories',
            'categories' => $this->categoryService->getAllData()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean'
        ]);

        $this->categoryService->store($validated);

        return redirect()->back()->with('success', 'New unit category data has been stored successfully');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'reason' => 'required|string',
        ]);

        $this->categoryService->update($id, $validated);

        return redirect()->back()->with('success', 'Unit category data updated successfully');
    }

    public function massDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:unit_categories,id',
            'remark' => 'required|string'
        ]);

        $this->categoryService->massDelete($validated);

        return redirect()->back()->with('success', 'Unit categories data deleted successfully');
    }

    public function getLog(Request $request, int $id)
    {
        try {
            $logs = $this->categoryService->getLogsData($id);

            return response()->json($logs);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'bulk_error' => $e->getMessage()
            ]);
        }
    }
}
