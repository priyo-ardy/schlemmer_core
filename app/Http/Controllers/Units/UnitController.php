<?php

namespace App\Http\Controllers\Units;

use App\Http\Controllers\Controller;
use App\Services\Units\UnitService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UnitController extends Controller
{
    protected UnitService $unitService;

    public function __construct(UnitService $unitService)
    {
        $this->unitService = $unitService;
    }

    public function index()
    {
        return Inertia::render('Units/Unit', [
            'units' => $this->unitService->getAllData()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string|max:10|unique:units,symbol',
            'name' => 'required|string|max:150',
            'is_active' => 'required|boolean',
            'remark' => 'nullable|string'
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
            'is_active' => 'required|boolean',
            'name' => 'required|string|max:150',
            'remark' => 'nullable|string'
        ]);

        $this->unitService->update($id, $validated);

        return redirect()->back()->with('success', 'UoM data updated successfully');
    }

    public function massDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:units,id'
        ]);

        $this->unitService->massDelete($request->ids);

        return redirect()->back()->with('success', 'Data deleted successfully');
    }
}
