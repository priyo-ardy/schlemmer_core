<?php

namespace App\Http\Controllers\api\v1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerResource;
use App\Services\Customer\CustomerService;
use Illuminate\Http\Request;

class CustomerApiController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
    ) {}

    public function list(Request $request)
    {
        $materials = $this->customerService->getList();
    }

    public function dropdown(Request $request)
    {
        $search = $request->query('search');

        $customers = $this->customerService->searchCustomer($search);

        return response()->json([
            'data'     => CustomerResource::collection($customers->items()),
            'has_more' => $customers->hasMorePages(),
        ]);
    }
}
