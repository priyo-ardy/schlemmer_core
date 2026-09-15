<?php

namespace App\Repositories\ApprovalTransaction;

use App\Models\ApprovalTransaction;

class ApprovalTransactionRepository
{
    public function store(array $data)
    {
        return ApprovalTransaction::create($data);
    }
}
