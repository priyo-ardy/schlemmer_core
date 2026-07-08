<?php

namespace App\Http\Controllers\api\v1\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\CustomerService;
use Illuminate\Http\Request;

class CustomerApiController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
    ) {}

    public function list(Request $request)
    {
        $search = $request->query('search');

        $customers = $this->customerService->getLists($search);

        return response()->json($customers);
    }
}
