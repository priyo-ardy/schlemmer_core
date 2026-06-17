<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use App\Models\ProcessDetail;
use App\Models\ProcessHeader;
use App\Services\ProcessTemplate\ProcessTemplateServices;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProcessController extends Controller
{
    protected $processService;

    public function __construct(ProcessTemplateServices $processService)
    {
        $this->processService = $processService;
    }

    public function index()
    {
        return Inertia::render('Process/process-list');
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

        return Inertia::render('Process/view', [
            'header' => $header,
        ]);
    }
}
