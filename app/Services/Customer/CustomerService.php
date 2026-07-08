<?php

namespace App\Services\Customer;

use App\Models\ChangeLogs;
use App\Models\Customer;
use App\Repositories\Customer\CustomerRepository;
use App\Services\ChangeLogs\ChangeLogsService;
use App\Services\GenerateCode\AutoNumberService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class CustomerService
{
    public function __construct(
        protected CustomerRepository $customerRepo,
        protected ChangeLogsService $logService,
        protected AutoNumberService $autoNumber
    ) {}

    public function getAllData($filter, $page, $search = null)
    {
        try {
            return $this->customerRepo->getAllData($filter, $page, $search);
        } catch (\Exception $e) {
            activity('get_all_customer')
                ->causedBy(Auth::id())
                ->withProperties([
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip(),
                ])
                ->log('Load failed: Failed to load customer data');
            throw $e;
        }
    }

    public function store(array $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $generatedCode = $this->autoNumber->generate('customer');

                $dataInsert = [
                    'revision' => 0,
                    'code' => $generatedCode,
                    'name' => isset($data['name']) ? trim($data['name']) : '',
                    'alias' => filled($data['alias'] ?? null) ? trim($data['alias']) : null,
                    'tax_number' => filled($data['tax_number'] ?? null) ? trim($data['tax_number']) : null,
                    'tier_level' => $data['tier_level'] ?? 'tier-1',
                    'csr_reference_doc' => filled($data['csr_reference_doc'] ?? null) ? trim($data['csr_reference_doc']) : null,
                    'risk_profile' => $data['risk_profile'] ?? 'medium',
                    'email' => filled($data['email'] ?? null) ? trim($data['email']) : null,
                    'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
                    'billing_address' => filled($data['billing_address'] ?? null) ? trim($data['billing_address']) : null,
                    'shipping_address' => filled($data['shipping_address'] ?? null) ? trim($data['shipping_address']) : null,
                    'is_active' => $data['is_active'] ?? true,
                    'remark' => filled($data['remark'] ?? null) ? trim($data['remark']) : null,
                ];

                $insertData = $this->customerRepo->create($dataInsert);

                $this->logService->store($insertData, 'create', 'register new customer data', null, $insertData->toArray());

                activity('save_new_customer')
                    ->causedBy(Auth::id())
                    ->withProperties([
                        'data' => $insertData->toArray(),
                        'ip' => Request::ip(),
                    ])
                    ->log('Save success: Successfully register new customer data');

                return $insertData;
            });
        } catch (\Exception $e) {
            activity('save_new_customer')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_data' => $data,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip(),
                ])
                ->log('Save failed: Failed to register new customer data');
            throw $e;
        }
    }

    public function getData(int $id)
    {
        return $this->customerRepo->findById($id);
    }

    public function update(int $id, array $data)
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $old = $this->customerRepo->findById($id);

                if (! $old) {
                    throw new \Exception('Customer not found.');
                }

                $dataUpdate = [
                    'name' => isset($data['name']) ? trim($data['name']) : '',
                    'alias' => filled($data['alias'] ?? null) ? trim($data['alias']) : null,
                    'tax_number' => filled($data['tax_number'] ?? null) ? trim($data['tax_number']) : null,
                    'revision' => $old->revision + 1,
                    'tier_level' => $data['tier_level'] ?? 'tier-1',
                    'csr_reference_doc' => filled($data['csr_reference_doc'] ?? null) ? trim($data['csr_reference_doc']) : null,
                    'risk_profile' => $data['risk_profile'] ?? 'medium',
                    'email' => filled($data['email'] ?? null) ? trim($data['email']) : null,
                    'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
                    'billing_address' => filled($data['billing_address'] ?? null) ? trim($data['billing_address']) : null,
                    'shipping_address' => filled($data['shipping_address'] ?? null) ? trim($data['shipping_address']) : null,
                    'is_active' => $data['is_active'] ?? true,
                    'remark' => filled($data['remark'] ?? null) ? trim($data['remark']) : null,
                ];

                $update = $this->customerRepo->update($id, $dataUpdate);

                $newData = $this->customerRepo->findById($id);

                $reason = filled($data['remark'] ?? null) ? trim($data['remark']) : 'Update customer details';

                $this->logService->store($newData, 'update', $reason, $old->toArray(), $newData->toArray());

                activity('update_customer')
                    ->causedBy(Auth::id())
                    ->withProperties([
                        'input_id' => $id,
                        'input_data' => $newData->toArray(),
                        'ip' => Request::ip(),
                    ])
                    ->log('Update success: Successfully udated customer data');

                return $update;
            });
        } catch (\Exception $e) {
            activity('update_customer')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $id,
                    'input_data' => $data,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip(),
                ])
                ->log('Update failed: Failed to update customer data');
            throw $e;
        }
    }

    // Buat single delete
    public function delete(int $id, string $reason)
    {
        try {
            return DB::transaction(function () use ($id, $reason) {
                $customer = $this->customerRepo->findById($id);

                if (! $customer) {
                    throw new \Exception("Customer data not found for ID: {$id}");
                }

                $oldData = $customer->toArray();

                $isDeleted = $this->customerRepo->delete($id);

                activity('delete_customer')
                    ->causedBy(Auth::id())
                    ->performedOn($customer)
                    ->withProperties([
                        'input_id' => $id,
                        'old_data' => $oldData,
                        'ip' => Request::ip(),
                    ])
                    ->log('Delete success: Successfully deleted customer data');

                $this->logService->store($customer, 'delete', $reason, $oldData, null);

                return $isDeleted;
            });
        } catch (\Exception $e) {
            activity('delete_customer')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $id,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip(),
                ])
                ->log('Delete failed: Failed to delete customer data');

            throw $e;
        }
    }

    // Buat bulk delete
    public function massDelete(array $ids, string $reason)
    {
        try {
            return DB::transaction(function () use ($ids, $reason) {
                $customers = $this->customerRepo->findManyByIds($ids);

                if ($customers->isEmpty()) {
                    throw new \Exception('No customer data found for the provided IDs.');
                }

                foreach ($customers as $customer) {
                    $oldData = $customer->toArray();

                    activity('mass_delete_customer')
                        ->causedBy(Auth::id())
                        ->performedOn($customer)
                        ->withProperties([
                            'input_id' => $customer->id,
                            'old_data' => $oldData,
                            'ip' => Request::ip(),
                        ])
                        ->log('Mass Delete Success: Successfully deleted customer data');

                    $this->logService->store($customer, 'delete', $reason, $oldData, null);
                }

                $deletedCount = $this->customerRepo->deleteAll($ids);

                return $deletedCount;
            });
        } catch (\Exception $e) {
            activity('mass_delete_customer')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $ids,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip(),
                ])
                ->log('Delete failed: Failed to mass delete customer data');

            throw $e;
        }
    }

    public function getLogsData(int $id): Collection
    {
        try {
            return ChangeLogs::with('creator')
                ->where('item_id', $id)
                ->where('table_name', 'projects')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            activity('get_logs_data')
                ->causedBy(Auth::id())
                ->withProperties([
                    'input_id' => $id,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => Request::ip(),
                ])
                ->log('Load failed: Failed to load revision history data');

            throw $e;
        }
    }

    public function getCustomerList()
    {
        return Customer::select('id', 'code', 'name')->orderBy('code', 'asc')->get();
    }

    public function searchCustomer($search)
    {
        return $this->customerRepo->search($search);
    }

    public function getLists($search)
    {
        return $this->customerRepo->getLists($search);
    }
}
