<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use App\Models\ProcessHeader;
use App\Services\ProcessTemplate\ProcessTemplateServices;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProcessController extends Controller
{
    protected $processService;

    public function __construct(ProcessTemplateServices $processService)
    {
        $this->processService = $processService;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $headers = ProcessHeader::with(['details', 'updater'])
            ->when($request->search, function ($query, $search) {
                // Mencari berdasarkan nama atau remark
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%");
            })
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString(); // Wajib agar search query terbawa saat pindah halaman

        return Inertia::render('Process/process-list', [
            'headers' => $headers,
            'filters' => $request->only(['search', 'per_page']) // Kirim balik filter ke FE
        ]);
    }

    public function create()
    {
        return Inertia::render('Process/create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:process_functions,name',
            'remark' => 'nullable|string',

            // Validasi Array Details
            'processItems' => 'required|array|min:1',
            'processItems.*.previous_problem' => 'nullable|string|max:255',
            'processItems.*.requirements' => 'required|string|max:255',
            'processItems.*.potential_failure_mode' => 'required|string|max:255',
            'processItems.*.potential_effect_of_failure' => 'required|string|max:255',
            'processItems.*.potential_cause_of_failure' => 'required|string|max:255',
            'processItems.*.controls_prevention' => 'required|string|max:255',
            'processItems.*.controls_detection' => 'required|string|max:255',
        ]);

        $template_data = $this->processService->storedData($validated);

        return redirect()
            ->route('process.view', $template_data->id)
            ->with('success', 'Process function data saved successfully');
    }

    public function view($id)
    {
        $header = ProcessHeader::with('details')->findOrFail($id);
        $allIds = ProcessHeader::orderBy('id')->pluck('id');

        return Inertia::render('Process/view', [
            'header' => $header,
            'allIds' => $allIds
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('process_functions', 'name')->ignore($id),
            ],
            'remark' => 'nullable|string',

            // Validasi Array Details
            'processItems' => 'required|array|min:1',
            'processItems.*.previous_problem' => 'nullable|string|max:255',
            'processItems.*.requirements' => 'required|string|max:255',
            'processItems.*.potential_failure_mode' => 'required|string|max:255',
            'processItems.*.potential_effect_of_failure' => 'required|string|max:255',
            'processItems.*.potential_cause_of_failure' => 'required|string|max:255',
            'processItems.*.controls_prevention' => 'required|string|max:255',
            'processItems.*.controls_detection' => 'required|string|max:255',
        ]);

        $this->processService->updateData($id, $validated);

        return redirect()
            ->route('process.view', $id)
            ->with('success', 'PMFEA template data updated successfully');
    }
}
