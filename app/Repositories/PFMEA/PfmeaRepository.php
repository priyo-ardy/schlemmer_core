<?php

namespace App\Repositories\PFMEA;

use App\Models\ChangeLogs;
use App\Models\Material;
use App\Models\PfmeaCoreTeam;
use App\Models\PfmeaDetail;
use App\Models\PfmeaHeader;
use Illuminate\Database\Eloquent\Collection;

class PfmeaRepository
{
    public function searchData($search) {}

    public function getPfmeaList($filter = null, $per_page = 10, $search = null, $direction = 'asc')
    {
        $query = PfmeaHeader::with([
            'details.processFunction',
            'coreTeam.team:id,name',
            'department:id,code,name,short_name',
            'project:id,code,name',
            'material:id,code,name,specification,drawing_change',
            'creator:id,name',
            'updater:id,name'
        ]);

        // Order by relasi material.code menggunakan Subquery
        $query->orderBy(
            Material::select('code')
                ->whereColumn('materials.id', 'pfmea.material_id'),
            $direction // 'asc' atau 'desc'
        );

        // Filter status jika ada
        if ($filter && $filter !== 'all') {
            $query->where('is_active', $filter === 'enable' ? 1 : 0);
        }

        // Filter pencarian
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                // 1. Pencarian di Kolom Utama (Tabel pfmea)
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('version', 'LIKE', "%{$search}%")
                    ->orWhere('date', 'LIKE', "%{$search}%")
                    ->orWhere('scope', 'LIKE', "%{$search}%")
                    ->orWhere('process_responsibility', 'LIKE', "%{$search}%");

                // 2. Search Relasi: Core Team -> Name
                $q->orWhereHas('coreTeam.team', function ($qTeam) use ($search) {
                    $qTeam->where('name', 'LIKE', "%{$search}%");
                });

                // 3. Search Relasi: Department -> Name & Short Name
                $q->orWhereHas('department', function ($qDept) use ($search) {
                    $qDept->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('short_name', 'LIKE', "%{$search}%");
                });

                // 4. Search Relasi: Project -> Code & Name
                $q->orWhereHas('project', function ($qProj) use ($search) {
                    $qProj->where('code', 'LIKE', "%{$search}%")
                        ->orWhere('name', 'LIKE', "%{$search}%");
                });

                // 5. Search Relasi: Creator -> Name
                $q->orWhereHas('creator', function ($qUser) use ($search) {
                    $qUser->where('name', 'LIKE', "%{$search}%");
                });

                // 6. Search Relasi: Material -> Code, Name, & Specification
                $q->orWhereHas('material', function ($qMat) use ($search) {
                    $qMat->where('code', 'LIKE', "%{$search}%")
                        ->orWhere('name', 'LIKE', "%{$search}%")
                        ->orWhere('specification', 'LIKE', "%{$search}%");
                });

                // 7. Search Relasi: Updater -> Name
                $q->orWhereHas('updater', function ($qUser) use ($search) {
                    $qUser->where('name', 'LIKE', "%{$search}%");
                });
            });
        }

        return $query->paginate($per_page)->withQueryString();
    }

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

    public function getLogs($id)
    {
        return ChangeLogs::where('item_id', $id)
            ->where('table_name', 'pfmea')
            ->orderBy('created_at', 'DESC')
            ->get();
    }
}
