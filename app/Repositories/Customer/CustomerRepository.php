<?php

namespace App\Repositories\Customer;

use App\Http\Resources\Customer\CustomerResource;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository
{
    public function getAllData($filter, $page, $search = null)
    {
        $query = Customer::orderBy('code', 'asc');

        if ($filter && $filter !== 'all') {
            $query->where('is_active', $filter === 'enable' ? 1 : 0);
        }

        if ($search) {
            // BUNGKUS DENGAN CLOSURE BIAR AMAN!
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('alias', 'like', "%{$search}%")
                    ->orWhere('tax_number', 'like', "%{$search}%")
                    ->orWhere('tier_level', 'like', "%{$search}%")
                    ->orWhere('csr_reference_doc', 'like', "%{$search}%")
                    ->orWhere('risk_profile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('billing_address', 'like', "%{$search}%")
                    ->orWhere('shipping_address', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        return $query->paginate($page)->withQueryString();
    }

    public function findById(int $id): ?Customer
    {
        return Customer::find($id);
    }

    public function findManyByIds(array $ids): Collection
    {
        return Customer::whereIn('id', $ids)->get();
    }

    public function findByCode(string $code): ?Customer
    {
        return Customer::where('code', $code)->first();
    }

    public function create(array $data): ?Customer
    {
        return Customer::create($data);
    }

    public function update(int $id, array $data)
    {
        return Customer::where('id', $id)->update($data);
    }

    public function delete(int $id): ?Customer
    {
        return Customer::delete($id);
    }

    public function deleteAll(array $ids)
    {
        return Customer::whereIn('id', $ids)->delete();
    }

    public function search($search)
    {
        $customers = Customer::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('alias', 'LIKE', "%{$search}%");
            })
            ->where('is_active', 1)
            ->paginate(10);

        return CustomerResource::collection($customers);
    }

    public function getLists()
    {
        $customers = Customer::orderBy('code', 'asc')->get();

        return $customers;
    }

    public function getDataByUuid($uuid)
    {
        return Customer::where('uuid', $uuid)->first();
    }
}
