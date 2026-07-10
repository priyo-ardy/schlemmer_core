<?php

namespace App\Repositories\Project;

use App\Models\Project;
use App\Models\ProjectMaterial;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository
{

    public function getAllData($filter, $page, $search = null)
    {
        // return Project::orderBy('code', 'asc')->get();
        $query = Project::with('customer')->orderBy('code', 'asc');

        if ($filter && $filter !== 'all') {
            $query->where('is_active', $filter === 'enable' ? 1 : 0);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('vehicle_model', 'like', "%{$search}%")
                    ->orWhere('main_part_number', 'like', "%{$search}%")
                    ->orWhere('main_part_name', 'like', "%{$search}%")
                    ->orWhere('apqp_phase', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('kick_off_date', 'like', "%{$search}%")
                    ->orWhere('target_proto_date', 'like', "%{$search}%")
                    ->orWhere('target_ppap_date', 'like', "%{$search}%")
                    ->orWhere('target_sop_date', 'like', "%{$search}%")
                    ->orWhere('confidentiality_level', 'like', "%{$search}%")
                    ->orWhere('revision', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        return $query->paginate($page);
    }

    public function getDataById($id): ?Project
    {
        return Project::find($id);
    }

    public function getDetails($id): Collection
    {
        return ProjectMaterial::where('project_id', $id)->get();
    }

    public function findById(int $id): ?Project
    {
        return Project::find($id);
    }

    public function store(array $data): ?Project
    {
        return Project::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $project = Project::find($id);

        if (!$project) {
            return false;
        }

        return $project->update($data);
    }

    public function findManyByIds(array $ids): Collection
    {
        return Project::whereIn('id', $ids)->get();
    }

    public function deleteData($id): ?Project
    {
        $project = Project::find($id);

        if ($project) {
            $project->delete();
        }

        return $project;
    }

    public function deleteAll(array $ids)
    {
        return Project::whereIn('id', $ids)->delete();
    }

    public function saveDetails(Project $project, array $data): Collection
    {
        return $project->details()->createMany($data);
    }

    public function deleteDetailsNotIn($idHeader, array $keptIds)
    {
        return ProjectMaterial::where('project_id', $idHeader)
            ->whereNotIn('id', $keptIds)
            ->delete();
    }

    public function upsertDetails(array $details)
    {
        if (empty($details)) return;

        return ProjectMaterial::upsert(
            $details,
            ['uuid'],
            ['material_id', 'updated_by', 'updated_at']
        );
    }
}
