<?php

namespace App\Repositories\Material;

use App\Models\Material;
use Illuminate\Database\Eloquent\Collection;

class MaterialRepository
{
    public function store(array $data): Material
    {
        return Material::create($data);
    }

    public function getDataById(int $materialId): ?Material
    {
        return Material::find($materialId);
    }

    public function getDataByCode(string $materialCode): ?Material
    {
        return Material::query()
            ->where('part_number', $materialCode)
            ->first();
    }

    public function update(int $id, array $data): bool
    {
        $material = Material::find($id);

        if (!$material) {
            return false;
        }

        return $material->update($data);
    }

    public function delete(int $id): bool
    {
        $material = Material::find($id);

        if (!$material) {
            return false;
        }

        return $material->delete();
    }

    public function findManyByIds($ids): Collection
    {
        return Material::whereIn('id', $ids)->get();
    }

    public function getMaterialList($filter, $per_page, $search = null)
    {
        $query = Material::with(['creator', 'updater', 'units'])->orderBy('code', 'asc');

        if ($filter && $filter !== 'all') {
            $query->where('is_active', $filter === 'enable' ? 1 : 0);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('revision', 'LIKE', "%{$search}%")
                    ->orWhere('category', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%")
                    ->orWhere('specification', 'LIKE', "%{$search}%")
                    ->orWhere('customer_part_name', 'LIKE', "%{$search}%")
                    ->orWhere('unit_id', 'LIKE', "%{$search}%")
                    ->orWhere('grade', 'LIKE', "%{$search}%")
                    ->orWhere('density', 'LIKE', "%{$search}%")
                    ->orWhere('melt_flow_index', 'LIKE', "%{$search}%")
                    ->orWhere('color', 'LIKE', "%{$search}%")
                    ->orWhere('shrinkage_rate', 'LIKE', "%{$search}%")
                    ->orWhere('gross_weight', 'LIKE', "%{$search}%")
                    ->orWhere('net_weight', 'LIKE', "%{$search}%")
                    ->orWhere('sprue_weight', 'LIKE', "%{$search}%")
                    ->orWhere('has_rohs', 'LIKE', "%{$search}%")
                    ->orWhere('imds_number', 'LIKE', "%{$search}%")
                    ->orWhere('msds_doc_path', 'LIKE', "%{$search}%")
                    ->orWhere('risk_profile', 'LIKE', "%{$search}%")
                    ->orWhere('remark', 'LIKE', "%{$search}%");
            });
        }

        return $query->paginate($per_page)->withQueryString();
    }

    public function deleteAll(array $ids)
    {
        return Material::whereIn('id', $ids)->delete();
    }

    public function searchMaterial($search)
    {
        $materials = Material::query()
            ->where('is_active', 1)
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('code', 'LIKE', "%{$search}%")
                        ->orWhere('name', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('code', 'asc')
            ->paginate(10);

        return $materials;
    }

    public function getAllData()
    {
        return Material::orderBy('code', 'asc')->get();
    }
}
