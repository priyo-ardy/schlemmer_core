<?php

namespace App\Services\MaterialService;

use App\Models\Material;
use App\Models\MaterialLogs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaterialService
{
    public function store(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $data['created_by'] = Auth::id();

                $material = Material::create($data);

                $this->logEvent($material, 'create', 'Register new material data', null, $material->toArray());

                return $material;
            });
        } catch (\Exception $e) {
            Log::error('Failed to save new material data');
            activity()
                ->causedBy(Auth::id())
                ->withProperties([
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ])
                ->log('save error: failed to save new material data');

            throw $e;
        }
    }

    public function getMaterialData($id)
    {
        try {
            return Material::find($id);
        } catch (\Exception) {
            Log::error('Material data not found');
            activity()
                ->causedBy(Auth::id())
                ->withProperties([
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ])
                ->log('retrieve error: failed to retrieve material data');

            throw $e;
        }
    }

    public function update(Material $material, array $data, string $reason)
    {
        try {
            return DB::transaction(function () use ($material, $data, $reason) {
                $oldData = $material->toArray();

                $data['updated_by'] = Auth::id();
                $data['revision'] = $material->revision + 1;

                $material->update($data);

                $newData = $material->fresh()->toArray();

                $this->logEvent($material, 'update', $reason, $oldData, $newData);

                return $material;
            });
        } catch (\Exception $e) {
            Log::error('Failed to update material data');
            activity()
                ->causedBy(Auth::id())
                ->withProperties([
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ])
                ->log('save error: failed to update material data');

            throw $e;
        }
    }

    public function bulkDelete(array $ids, string $reason)
    {
        try {
            return DB::transaction(function () use ($ids, $reason) {
                $materials = Material::whereIn('id', $ids)->get();

                foreach ($materials as $material) {
                    $this->logEvent($material, 'delete', $reason, $material->toArray(), null);
                }

                return Material::whereIn('id', $ids)->delete();
            });
        } catch (\Exception $e) {
            Log::error('Failed to bulk delete material data');
            activity()
                ->causedBy(Auth::id())
                ->withProperties([
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ])
                ->log('save error: failed to bulk delete material data');

            throw $e;
        }
    }

    private function logEvent(Material $material, string $eventType, string $reason, ?array $old, ?array $new)
    {
        MaterialLogs::create([
            'material_id'   => $material->id,
            'user_id'       => Auth::id(),
            'event_type'    => $eventType,
            'change_reason' => $reason,
            'old_data'      => $old,
            'new_data'      => $new,
            'revision'      => $material->revision,
        ]);
    }
}
