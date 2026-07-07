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
            $query
                ->where('revision', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('specification', 'like', "%{$search}%")
                ->orWhere('customer_part_name', 'like', "%{$search}%")
                ->orWhere('unit_id', 'like', "%{$search}%")
                ->orWhere('grade', 'like', "%{$search}%")
                ->orWhere('density', 'like', "%{$search}%")
                ->orWhere('melt_flow_index', 'like', "%{$search}%")
                ->orWhere('color', 'like', "%{$search}%")
                ->orWhere('shrinkage_rate', 'like', "%{$search}%")
                ->orWhere('gross_weight', 'like', "%{$search}%")
                ->orWhere('net_weight', 'like', "%{$search}%")
                ->orWhere('sprue_weight', 'like', "%{$search}%")
                ->orWhere('has_rohs', 'like', "%{$search}%")
                ->orWhere('imds_number', 'like', "%{$search}%")
                ->orWhere('msds_doc_path', 'like', "%{$search}%")
                ->orWhere('risk_profile', 'like', "%{$search}%")
                ->orWhere('remark', 'like', "%{$search}%");
        }

        return $query->paginate($per_page);
    }

    public function deleteAll(array $ids)
    {
        return Material::whereIn('id', $ids)->delete();
    }
}
