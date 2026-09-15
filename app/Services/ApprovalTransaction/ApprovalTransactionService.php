<?php

namespace App\Services\ApprovalTransaction;

use App\Repositories\ApprovalTransaction\ApprovalTransactionRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalTransactionService
{
    public function __construct(
        protected ApprovalTransactionRepository $repo
    ) {}

    public function store(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                return $this->repo->store($data);
            });
        } catch (\Exception $e) {
            Log::error('General Error: ' . $e->getMessage());

            activity('save_approval_transaction')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_data' => $data,
                    'ip_address' => request()->ip(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ])
                ->log('Save failed: Failed to save approval transaction data.');
        }
    }
}
