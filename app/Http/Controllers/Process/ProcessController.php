<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use App\Models\ProcessHeader;
use App\Models\User;
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
                    ->orWhere('revision', "{$search}")
                    ->orWhere('remark', 'like', "%{$search}%");
            })
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString(); // Wajib agar search query terbawa saat pindah halaman

        return Inertia::render('Process/process-list', [
            'headers' => $headers,
            'filters' => $request->only(['search', 'per_page']),
            'page_title' => 'Master Data / Process Function Templat List'
        ]);
    }

    public function create()
    {
        return Inertia::render('Process/create', [
            'responsibility' => User::orderBy('name', 'asc')->get()
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:150',
                    // Validasi unik HANYA untuk data yang belum di-soft delete
                    Rule::unique('process_functions', 'name')->whereNull('deleted_at')
                ],
                'remark' => 'nullable|string',

                // Validasi Array Details
                'processItems'                                  => 'required|array|min:1',
                'processItems.*.previous_problem'               => 'nullable|string|max:255',
                'processItems.*.requirements'                   => 'required|string|max:255',
                'processItems.*.potential_failure_mode'         => 'required|string|max:255',
                'processItems.*.potential_effect_of_failure'    => 'required|string|max:255',
                'processItems.*.potential_cause_of_failure'     => 'required|string|max:255',
                'processItems.*.classification'                 => 'nullable|string|max:255',
                'processItems.*.occurrence'                     => 'required|integer|max_digits:11',
                'processItems.*.detection'                      => 'required|integer|max_digits:11',
                'processItems.*.rpn'                            => 'required|integer|max_digits:11',
                'processItems.*.severity'                       => 'required|integer|max_digits:11',
                'processItems.*.recommended_action'             => 'nullable|string|max:255',
                'processItems.*.controls_prevention'            => 'required|string|max:255',
                'processItems.*.controls_detection'             => 'required|string|max:255',
                'processItems.*.responsibility'                 => 'nullable|integer',
                'processItems.*.target_completion_date'         => 'nullable|date',
                'processItems.*.action_taken_completion_date'   => 'nullable|date',
                'processItems.*.result_severity'                => 'nullable|integer',
                'processItems.*.result_occurrence'              => 'nullable|integer',
                'processItems.*.result_detection'               => 'nullable|integer',
                'processItems.*.result_rpn'                     => 'nullable|integer',
            ]);

            $template_data = $this->processService->storedData($validated);

            return redirect()
                ->route('process.view', $template_data->id)
                ->with('success', 'PFMEA process function data, stored successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['errors' => 'Failed to save process function data, there was an error, ' . $e->getMessage()]);
        }
    }

    public function view($id)
    {
        $header = ProcessHeader::with('details')->findOrFail($id);
        $allIds = ProcessHeader::orderBy('id')->pluck('id');

        return Inertia::render('Process/view', [
            'header' => $header,
            'allIds' => $allIds,
            'responsibility' => User::orderBy('name', 'asc')->get()
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:150',
                    Rule::unique('process_functions', 'name')
                        ->ignore($id)
                        ->whereNull('deleted_at')
                ],
                'remark' => 'nullable|string',

                // Validasi Array Details
                'processItems'                                  => 'required|array|min:1',
                'processItems.*.previous_problem'               => 'nullable|string|max:255',
                'processItems.*.requirements'                   => 'required|string|max:255',
                'processItems.*.potential_failure_mode'         => 'required|string|max:255',
                'processItems.*.potential_effect_of_failure'    => 'required|string|max:255',
                'processItems.*.potential_cause_of_failure'     => 'required|string|max:255',
                'processItems.*.classification'                 => 'nullable|string|max:255',
                'processItems.*.occurrence'                     => 'required|integer|max_digits:11',
                'processItems.*.detection'                      => 'required|integer|max_digits:11',
                'processItems.*.rpn'                            => 'required|integer|max_digits:11',
                'processItems.*.severity'                       => 'required|integer|max_digits:11',
                'processItems.*.recommended_action'             => 'nullable|string|max:255',
                'processItems.*.controls_prevention'            => 'required|string|max:255',
                'processItems.*.controls_detection'             => 'required|string|max:255',
                'processItems.*.recommended_action'             => 'nullable|string|max:255',
                'processItems.*.controls_prevention'            => 'required|string|max:255',
                'processItems.*.controls_detection'             => 'required|string|max:255',
                'processItems.*.responsibility'                 => 'nullable|integer',
                'processItems.*.target_completion_date'         => 'nullable|date',
                'processItems.*.action_taken_completion_date'   => 'nullable|date',
                'processItems.*.result_severity'                => 'nullable|integer',
                'processItems.*.result_occurrence'              => 'nullable|integer',
                'processItems.*.result_detection'               => 'nullable|integer',
                'processItems.*.result_rpn'                     => 'nullable|integer',
            ]);

            $this->processService->updateData($id, $validated);

            return redirect()
                ->route('process.view', $id)
                ->with('success', 'PFMEA template data updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['errors' => 'Failed to update process function data, there was an error during processing your request, ' . $e->getMessage()]);
        }
    }

    public function getChangeLogs($headerId)
    {
        $logs = $this->processService->getLogs($headerId);

        return response()->json($logs);
    }

    public function getChangeLogsDetails($logId)
    {
        $details = $this->processService->getLogDetail($logId);

        return response()->json($details);
    }

    public function massDelete(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:process_functions,id',
                'remark' => 'required|string'
            ]);

            $this->processService->deleteAll($validated);

            return redirect()->back()->with('success', 'Successfully deleted process function data');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Failed to delete process data: ' . $e->getMessage()
            ]);
        }
    }

    public function getLog(Request $request, int $id)
    {
        try {
            $logs = $this->processService->getLogsData($id);

            return response()->json($logs);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'bulk_error' => $e->getMessage()
            ]);
        }
    }
}
