<?php

namespace App\Repositories\Customer;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository
{
    public function getAllData($page, $search = null)
    {
        $query = Customer::orderBy('code', 'asc');

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

        return $query->paginate($page);
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
}
