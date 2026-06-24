<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('Customer/List', [
            'customers' => $this->customerService->getAllData(
                $request->input('filter'),
                $request->input('per_page', 10),
                $request->input('search'),
            ),
            'page_title' => 'Master Data / Customer Management'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'revision' => 'nullable',
            'code' => 'nullable',
            'name' => 'required|string|max:150',
            'alias' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'tier_level' => 'nullable|string',
            'csr_reference_doc' => 'nullable|string',
            'risk_profile' => '',
            'email' => 'nullable|string',
            'phone' => 'nullable|string',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'remark' => 'nullable|string',
        ]);

        $this->customerService->store($validated);

        return redirect()->back()->with('success', 'New customer data has been stored successfully');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'revision' => 'nullable',
            'code' => 'nullable',
            'name' => 'required|string|max:150',
            'alias' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'tier_level' => 'nullable|string',
            'csr_reference_doc' => 'nullable|string',
            'risk_profile' => '',
            'email' => 'nullable|string',
            'phone' => 'nullable|string',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'remark' => 'nullable|string',
        ]);

        $this->customerService->update($id, $validated);

        return redirect()->back()->with('success', 'Customer data has updated successfully');
    }

    public function delete(Request $request) {}

    public function massDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:units,id',
            'remark' => 'required|string|min:5',
        ]);

        $this->customerService->massDelete($request->ids, $request->remark);

        return redirect()->back()->with('success', 'Data deleted successfully');
    }

    public function getLog(Request $request, int $id)
    {
        try {
            $logs = $this->customerService->getLogsData($id);

            return response()->json($logs);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'bulk_error' => $e->getMessage()
            ]);
        }
    }
}
