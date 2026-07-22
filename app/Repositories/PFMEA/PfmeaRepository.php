<?php

namespace App\Repositories\PFMEA;

use App\Models\PfmeaDetail;
use App\Models\PfmeaHeader;
use Illuminate\Database\Eloquent\Collection;

class PfmeaRepository
{
    public function searchData($search) {}

    public function getDataList() {}

    public function getDataById(int $id) {}

    public function getDetails($id_header) {}

    public function store(array $data): ?PfmeaHeader
    {
        return PfmeaHeader::create($data);
    }

    public function storeCoreTeam(PfmeaHeader $pfmea, array $data)
    {
        return $pfmea->coreTeam()->createMany($data);
    }

    public function storeDetails(PfmeaHeader $pfmea, array $data)
    {
        return $pfmea->details()->createMany($data);
    }



    public function findManyIds(array $ids): Collection
    {
        return PfmeaHeader::whereIn('id', $ids)->get();
    }

    public function update(array $data, int $id) {}

    public function deleteDetailsNotIn($id_header, array $keepIds)
    {
        return PfmeaDetail::where('pfmea_id', $id_header)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    public function upsertDetails(array $details)
    {
        if (empty($details)) return;

        return PfmeaDetail::upsert(
            $details,
            ['uuid'],
            ['order'],
            ['process_id'],
            ['created_by'],
            ['updated_by']
        );
    }

    public function delete(int $id): ?PfmeaHeader
    {
        $pfmea = PfmeaHeader::find($id);

        if ($pfmea) {
            $pfmea->delete();
        }

        return $pfmea;
    }

    public function massDelete(array $ids)
    {
        return PfmeaHeader::whereIn($ids)->delete();
    }
}
