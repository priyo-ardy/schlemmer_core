<?php

namespace App\Repositories\Units;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;

class UnitRepository
{
    public function findBySymbol(string $symbol): ?Unit
    {
        return Unit::where('symbol', $symbol)->first();
    }

    public function findById(int $id): ?Unit
    {
        return Unit::find($id);
    }

    public function create(array $data): ?Unit
    {
        return Unit::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $unit = Unit::find($id);

        if (!$unit) {
            return false;
        }

        return $unit->update($data);
    }

    public function delete(int $id): bool
    {
        $unit = Unit::find($id);

        if (!$unit) {
            return false;
        }

        return $unit->delete();
    }

    public function deleteAll(array $ids)
    {
        return Unit::whereIn('id', $ids)->delete();
    }

    public function getAllData()
    {
        return Unit::with(['creator', 'updater', 'category'])
            ->orderBy('code', 'asc')->get();
    }

    public function findManyByIds($ids): Collection
    {
        return Unit::whereIn('id', $ids)->get();
    }
}
