<?php

namespace App\Http\Controllers\ApprovalSetup;

use App\Http\Controllers\Controller;
use App\Models\ApprovalSetup;
use App\Services\ApprovalSetup\ApprovalSetupService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalSetupController extends Controller
{
    public function __construct(protected ApprovalSetupService $approvalSerice) {}

    public function index(Request $request)
    {
        return Inertia::render('ApprovalSetup/Index', [
            'approvals' => $this->approvalSerice->getAllData(
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
}
