<?php

namespace App\Http\Controllers\ApprovalSetup;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalSetup\StoreApprovalSetupRequest;
use App\Http\Requests\ApprovalSetup\UpdateApprovalSetupRequest;
use App\Models\ApprovalSetup;
use App\Services\ApprovalSetup\ApprovalSetupService;
use App\Services\Users\UserServices;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ApprovalSetupController extends Controller
{
    public function __construct(
        protected ApprovalSetupService $approvalService,
        protected UserServices $userService
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('ApprovalSetup/Index', [
            'approvals' => $this->approvalService->getAllData(
                $request->input('filter'),
                $request->input('per_page', 10),
                $request->input('search')
            ),
            'page_title' => 'Application Setting / Approval Management / Approval List'
        ]);
    }

    public function create(Request $reqeuest)
    {
        return Inertia::render('ApprovalSetup/Create', [
            'page_title' => 'Application Setting / Approval Management / Approval List / Create'
        ]);
    }

    public function store(StoreApprovalSetupRequest $request)
    {
        try {
            $save = $this->approvalService->store($request->validate());

            return to_route('approval-setup.view', [
                'id' => $save->id
            ])->with('success', 'Approval setup created successfully');
        } catch (QueryException $e) {
            Log::error('Database Error [ApprovalSetup]: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Database operation failed. Duplicate entry or constraint violation.');
        } catch (Exception $e) {
            Log::error('General Error [ApprovalSetupStore]: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage() ?: 'An unexpected error occurred while processing your request.');
        }
    }

    public function view(Request $request, $id)
    {
        return Inertia::render('ApprovalSetup/View', [
            'allIds' => $this->approvalService->getAll(),
            'header' => $this->approvalService->getData($id),
            'users' => $this->userService->getAllUsers(),
            'page_title' => 'Application Setting / Approval Management / Approval List / View'
        ]);
    }

    public function update(UpdateApprovalSetupRequest $request, int $id)
    {
        try {
            $update = $this->approvalService->update($request->validated(), $id);

            return redirect()->back()
                ->with('success', 'Approval setup updated successfully');
        } catch (QueryException $e) {
            Log::error('Database Error [ApprovalSetupUpdate]: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Database operation failed. Duplicate entry or constraint violation.');
        } catch (Exception $e) {
            Log::error('General Error [ApprovalSetupUpdate]: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage() ?: 'An unexpected error occurred while processing your request.');
        }
    }

    public function getLogs(int $id)
    {
        try {
            $logs = $this->approvalService->getLogs($id);

            return response()->json($logs);
        } catch (QueryException $e) {
            Log::error('Database Error [ApprovalSetupGetLogs]: ' . $e->getMessage());

            return response()->json([
                'message' => 'Database operation failed. Unable to fetch logs.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('General Error [ApprovalSetupGetLogs]: ' . $e->getMessage());

            return response()->json([
                'message' => $e->getMessage() ?: 'An unexpected error occurred while fetching logs.'
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'     => 'required|integer|exists:approval_setups,id',
                'reason' => 'required|string|min:3',
            ]);

            $this->approvalService->delete($validated);

            return to_route('approval-setup.index')
                ->with('success', 'Successfully deleted approval setup data');
        } catch (ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database Error [ApprovalSetupDelete]: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Database operation failed. Unable to delete data due to constraint violation.');
        } catch (\Exception $e) {
            Log::error('General Error [ApprovalSetupDelete]: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage() ?: 'An unexpected error occurred while processing your request.');
        }
    }

    public function massDelete(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:approval_setups,id',
                'reason' => 'required|string|min:5'
            ]);

            $delete = $this->approvalService->massDelete($validated);

            return redirect()->back()->with('success', 'Successfully deleted approval setup data');
        } catch (QueryException $e) {
            Log::error('Database Error [ApprovalSetupUpdate]: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Database operation failed. Duplicate entry or constraint violation.');
        } catch (Exception $e) {
            Log::error('General Error [ApprovalSetupUpdate]: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage() ?: 'An unexpected error occurred while processing your request.');
        }
    }
}
