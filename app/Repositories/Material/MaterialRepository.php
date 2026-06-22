<?php

namespace App\Repositories\Material;

use App\Models\Material;

class MaterialRepository
{
    public function store(array $data): Material
    {
        return Material::create($data);
    }

    public function getDataById(int $materialId)
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

    public function getMaterialList($page, $search = null)
    {
        $query = Material::orderBy('part_number', 'asc');

        if ($search) {
            $query->where('part_number', 'like', "%{$search}%")
                ->orWhere('part_name', 'like', "%{$search}%")
                ->orWhere('revision', 'like', "%{$search}%")
                ->orWhere('material_type', 'like', "%{$search}%")
                ->orWhere('unit_of_measure', 'like', "%{$search}%");
        }

        return $query->paginate($page);
    }

    public function bulkDelete(array $ids)
    {
        return Material::whereIn('id', $ids)->delete();
    }
}
