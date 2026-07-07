<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Services\Customer\CustomerService;
use App\Services\Project\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $projectService,
        protected CustomerService $customerService
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('Project/Project', [
            'projects' => $this->projectService->getAllData(
                $request->input('filter'),
                $request->input('per_page', 10),
                $request->input('search'),
            ),
            'customers' => $this->customerService->getCustomerList(),
            'page_title' => 'Master Data / Project Management / List of Project'
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Project/Create', [
            'page_title' => 'Master Data / Project Management / List of Project / Create'
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:projects,code|regex:/^[a-zA-Z0-9\-\/]+$/',
                'name' => 'required|string|max:150|regex:/^[a-zA-Z0-9\-\/\s]+$/',
                'customer_id' => 'required|exists:customers,id',
                'vehicle_model' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9\-\/\s]+$/',
                'main_part_number' => 'nullable|max:100|regex:/^[a-zA-Z0-9\-\/\s]+$/',
                'main_part_name' => 'nullable|max:150|regex:/^[a-zA-Z0-9\-\/\s]+$/',
                'apqp_phase' => 'nullable|max:50|regex:/^[a-zA-Z0-9\-\/\s]+$/',
                'status' => 'nullable|max:50|regex:/^[a-zA-Z0-9\-\/\s]+$/',
                'kick_off_date' => 'nullable|date',
                'target_proto_date' => 'nullable|date',
                'target_ppap_date' => 'nullable|date',
                'target_sop_date' => 'nullable|date',
                'confidentiality_level' => 'nullable|max:30',
                'revision' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
                'remark' => 'nullable|string|regex:/^[a-zA-Z0-9\-\/\s]+$/',
            ]);

            $this->projectService->store($validated);

            return redirect()->back()->with('success', 'Successfully saved new project data');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'errors' => $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    'regex:/^[a-zA-Z0-9\-\/]+$/',
                    Rule::unique('units', 'code')->ignore($id),
                ],
                'name' => 'required|string|max:150|regex:/^[a-zA-Z0-9\-\/\s]+$/',
                'customer_id' => 'required|exists:customers,id',
                'vehicle_model' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9\-\/\s]+$/',
                'main_part_number' => 'nullable|max:100|regex:/^[a-zA-Z0-9\-\/\s]+$/',
                'main_part_name' => 'nullable|max:150|regex:/^[a-zA-Z0-9\-\/\s]+$/',
                'apqp_phase' => 'nullable|max:50|regex:/^[a-zA-Z0-9\-\/\s]+$/',
                'status' => 'nullable|string',
                'kick_off_date' => 'nullable|date',
                'target_proto_date' => 'nullable|date',
                'target_ppap_date' => 'nullable|date',
                'target_sop_date' => 'nullable|date',
                'confidentiality_level' => 'nullable|max:30',
                'revision' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
                'remark' => 'nullable|string|regex:/^[a-zA-Z0-9\-\/\s]+$/',
                'reason' => 'required|string|regex:/^[a-zA-Z0-9\-\/\s]+$/'
            ]);

            $this->projectService->update($id, $validated);

            return redirect()->back()->with('success', 'Successfully updated project data');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'errors' => $e->getMessage()
            ]);
        }
    }

    public function massDelete(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:projects,id',
                'remark' => 'required'
            ]);

            $this->projectService->deleteAll($validated);

            return redirect()->back()->with('success', 'Successfully deleted project data');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'errors' => $e->getMessage()
            ]);
        }
    }

    public function getLog(Request $request, int $id)
    {
        try {
            $logs = $this->projectService->getLogsData($id);

            return response()->json($logs);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'bulk_error' => $e->getMessage()
            ]);
        }
    }
}
