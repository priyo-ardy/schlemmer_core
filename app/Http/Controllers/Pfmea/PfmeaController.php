<?php

namespace App\Http\Controllers\Pfmea;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\PfmeaHeader;
use App\Services\Department\DepartmentService;
use App\Services\MaterialService\MaterialService;
use App\Services\PFMEA\PfmeaService;
use App\Services\ProcessTemplate\ProcessTemplateServices;
use App\Services\Users\UserServices;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PfmeaController extends Controller
{
    public function __construct(
        protected PfmeaService $pfmeaService,
        protected UserServices $userService,
        protected MaterialService $materialService,
        protected DepartmentService $deptService,
        protected ProcessTemplateServices $processService,
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('Pfmea/Index', [
            'page_title' => 'PFMEA List'
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Pfmea/Create', [
            'users' => $this->userService->getAllUsers(),
            // 'materials' => $this->materialService->getMaterialList('all', 10, null),
            'departments' => DepartmentResource::collection($this->deptService->getAllData()),
            'page_title' => 'Create PFMEA Document'
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->merge([
                'code' => strtoupper(trim($request->input('code'))),
            ]);

            $validated = $request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('pfmea', 'code')
                ],
                'date' => 'required|date',
                'department_id' => 'required|exists:departments,id',
                'version' => 'nullable',
                'scope' => 'required',
                'project_id' => 'required|exists:projects,id',
                'material_id' => 'required|exists:materials,id',
                'process_responsibility' => 'required|string',

                // Core teams
                'core_teams' => 'required|array|min:1',
                'core_teams.*.id' => [
                    'required',
                    'exists:users,id',
                    'distinct'
                ],

                // details
                'details' => 'required|array|min:1',
                'details.*.process_id' => [
                    'required',
                    'exists:process_functions,id',
                    'distinct'
                ],
            ]);

            $insert = $this->pfmeaService->store($validated);

            return to_route('pfmea.view', ['id' => $insert->id])
                ->with('success', 'Successfully saved new PFMEA data');
        } catch (ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {

            if ($e->getCode() == 23000) {

                throw ValidationException::withMessages([
                    'code' => 'Code already exists.'
                ]);
            }

            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function view(Request $request, $id)
    {
        return Inertia::render('Pfmea/View', [
            'allIds' => PfmeaHeader::orderBy('id')->pluck('id'),
            'data' => $this->pfmeaService->getDataById($id),
            'users' => $this->userService->getAllUsers(),
            'departments' => DepartmentResource::collection($this->deptService->getAllData()),
            'page_title' => 'Create PFMEA Document'
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $request->merge([
                'code' => strtoupper(trim($request->input('code'))),
            ]);

            $validated = $request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('pfmea', 'code')->ignore($id)
                ],
                'date' => 'required|date',
                'department_id' => 'required|exists:departments,id',
                'version' => 'nullable',
                'scope' => 'required',
                'project_id' => 'required|exists:projects,id',
                'material_id' => 'required|exists:materials,id',
                'process_responsibility' => 'required|string',
                'reason' => 'nullable|string',

                // Core teams
                'core_teams' => 'required|array|min:1',
                'core_teams.*.id' => [
                    'required',
                    'exists:users,id',
                    'distinct'
                ],

                // details
                'details' => 'required|array|min:1',
                'details.*.process_id' => [
                    'required',
                    'exists:process_functions,id',
                    'distinct'
                ],
            ]);

            $update = $this->pfmeaService->update($validated, (int) $id);

            return to_route('pfmea.view', ['id' => $update->id])
                ->with('success', 'Successfully updated PFMEA data');
        } catch (ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {
            if ($e->getCode() == 23000) {
                throw ValidationException::withMessages([
                    'code' => 'Code already exists.'
                ]);
            }
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }
}
