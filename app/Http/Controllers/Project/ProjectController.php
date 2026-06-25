<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Services\Project\ProjectService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $projectService
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('Project/Project', [
            'projects' => $this->projectService->getAllData(
                $request->input('filter'),
                $request->input('per_page', 10),
                $request->input('search'),
            ),
            'page_title' => 'Master Data / Project Management / List of Project'
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([]);

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
            $validated = $request->validate([]);

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
