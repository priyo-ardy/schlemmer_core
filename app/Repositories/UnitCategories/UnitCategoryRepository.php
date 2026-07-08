<?php

namespace App\Repositories\UnitCategories;

use App\Models\UnitCategory;
use Illuminate\Database\Eloquent\Collection;

class UnitCategoryRepository
{
    public function findById(int $id): ?UnitCategory
    {
        return UnitCategory::find($id);
    }

    public function findManyByIds(array $ids): Collection
    {
        return UnitCategory::whereIn('id', $ids)->get();
    }

    public function getAllData()
    {
        return UnitCategory::orderBy('code', 'asc')->get();
    }

    public function create(array $data): ?UnitCategory
    {
        return UnitCategory::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $category = UnitCategory::find($id);

        if (!$category) {
            return false;
        }

        return $category->update($data);
    }

    public function deleteAll(array $ids)
    {
        return UnitCategory::whereIn('id', $ids)->delete();
    }

    public function getLists($search)
    {
        $categories = UnitCategory::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            })
            ->where('is_active', 1)
            ->paginate(100);

        return $categories;
    }
}
