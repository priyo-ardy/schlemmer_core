<?php

namespace App\Http\Controllers\RecycleBin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitCategory;
use App\Models\User;
use App\Services\ChangeLogs\ChangeLogsService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RecycleBinController extends Controller
{

    public function __construct(
        protected ChangeLogsService $logService
    ) {}

    public function index()
    {
        $masterModel = [
            'User' => User::class,
            'Customer' => Customer::class,
            'Material' => Material::class,
            'UnitCategory' => UnitCategory::class,
            'Unit' => Unit::class,
            'Project' => Project::class,
            'Material' => Material::class
        ];


        $trashList = collect();

        foreach ($masterModel as $resourceName => $modelClass) {
            $deletedItems = $modelClass::onlyTrashed()
                ->get();

            foreach ($deletedItems as $item) {
                $identitas = $item->code ? "[{$item->code}] {$item->name}" : $item->name;
                $trashList->push([
                    'id'           => $item->id,
                    'uuid'         => $item->uuid,
                    'resource'     => $resourceName,
                    'identifier'   => $identitas,
                    'deleted_at'   => $item->deleted_at,
                    'deleted_by'   => $item->deleted_by ?? null,
                ]);
            }
        }

        $sortedTrash = $trashList->sortByDesc('deleted_at')->values()->all();

        return Inertia::render('RecycleBin/Index', [
            'trashItems' => $sortedTrash,
            'page_title' => 'Application Setting / Recycle Bin'
        ]);
    }

    public function restore(Request $request)
    {
        $request->validate([
            'id'       => 'required|integer',
            'remark'   => 'nullable|string',
            'resource' => 'required|string',
        ]);

        $modelMap = [
            'User'          => User::class,
            'Customer'      => Customer::class,
            'Material'      => Material::class,
            'UnitCategory'  => UnitCategory::class,
            'Unit'          => Unit::class,
            'Project'       => Project::class,
            'Material'      => Material::class
        ];

        if (!array_key_exists($request->resource, $modelMap)) {
            return redirect()->back()->withErrors(['error' => 'Invalid resource type.']);
        }

        $modelClass = $modelMap[$request->resource];

        try {
            $item = $modelClass::onlyTrashed()->findOrFail($request->id);

            $beforeData = $item->toArray();
            $item->restore();
            $afterData = $item->toArray();

            $reason = $request->input('remark') ?? 'Restore deleted data via Recycle Bin';
            $this->logService->store($item, 'restore', $reason, $beforeData, $afterData);

            activity('restore_data')
                ->causedBy(Auth::id())
                ->withProperties([
                    'resource' => $request->resource,
                    'item_id'  => $request->id,
                    'remark'   => $reason
                ])
                ->log("Restored {$request->resource}: " . ($item->name ?? $item->id));

            return redirect()->back()->with('success', "Data {$request->resource} restored!");
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->withErrors([
                'error' => "Restore failed! Data {$request->resource} with ID {$request->id} not found in recycle bin."
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => "System failure: " . $e->getMessage()
            ]);
        }
    }
}
