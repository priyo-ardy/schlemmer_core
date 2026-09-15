<?php

namespace App\Http\Controllers\ApprovalTransaction;

use App\Http\Controllers\Controller;
use App\Services\ApprovalTransaction\ApprovalTransactionService;
use Illuminate\Http\Request;

class ApprovalTransactionController extends Controller
{
    public function __construct(
        protected ApprovalTransactionService $service
    ) {}

    public function index(Request $request) {}

    public function show() {}

    public function approve() {}

    public function reject() {}
}
