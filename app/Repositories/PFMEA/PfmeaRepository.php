<?php

namespace App\Repositories\PFMEA;

use App\Models\PfmeaCoreTeam;
use App\Models\PfmeaDetail;
use App\Models\PfmeaHeader;
use Illuminate\Database\Eloquent\Collection;

class PfmeaRepository
{
    public function searchData($search) {}

    public function getDataList() {}

    public function getDataById(int $id): ?PfmeaHeader
    {
        return PfmeaHeader::with([
            'department:id,code,name,short_name',
            'project:id,code,name',
            'material:id,code,name,specification,drawing_change',
            'creator:id,name',
            'updater:id,name'
        ])->find($id);
    }

    public function getCoreTeamByPfmeaId($pfmea_id): ?Collection
    {
        return PfmeaCoreTeam::with(['team:id,name'])
            ->where('pfmea_id', $pfmea_id)
            ->orderBy('order', 'asc')
            ->get();
    }

    public function getDetails($pfmea_id): ?Collection
    {
        return PfmeaDetail::with(['processFunction:id,name,sequence,process_parent,process_child,revision'])
            ->where('pfmea_id', $pfmea_id)
            ->orderBy('order', 'asc')
            ->get();
    }

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

    public function updateHeader(array $data, $id): ?PfmeaHeader
    {
        $pfmea = PfmeaHeader::find($id);

        if (!$pfmea) {
            return null;
        }

        $pfmea->update($data);

        return $pfmea;
    }

    public function deleteCoreTeamsByPfmeaId($pfmea_id)
    {
        return PfmeaCoreTeam::where('pfmea_id', $pfmea_id)->delete();
    }

    public function deleteDetailsByPfmeaId($pfmea_id)
    {
        return PfmeaDetail::where('pfmea_id', $pfmea_id)->delete();
    }

    public function findManyIds(array $ids): Collection
    {
        return PfmeaHeader::whereIn('id', $ids)->get();
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
        return PfmeaHeader::whereIn('id', $ids)->delete();
    }
}
