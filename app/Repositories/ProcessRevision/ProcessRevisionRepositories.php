<?php

namespace App\Repositories\ProcessRevision;

use App\Models\ProcessChangeLogRevision;
use App\Models\ProcessDetailRevision;
use App\Models\ProcessHeaderRevision;
use Illuminate\Support\Facades\DB;

class ProcessRevisionRepositories
{
    public function storeHeader(array $data): ProcessHeaderRevision
    {
        return ProcessHeaderRevision::create($data);
    }

    public function storeDetail(array $rows): bool
    {
        return ProcessDetailRevision::insert($rows);
    }

    public function storeLog($data)
    {
        return ProcessChangeLogRevision::create($data);
    }

    public function copyDetailsToRevision(int $headerId, int $revisionHeaderId): bool
    {
        $revisionTable = (new ProcessDetailRevision)->getTable();

        return DB::insert("
            INSERT INTO {$revisionTable} (
                revision_header_id, detail_uuid, `order`, previous_problem, requirements,
                potential_failure_mode, potential_effect_of_failure, potential_cause_of_failure,
                controls_prevention, controls_detection, created_at, updated_at
            )
            SELECT 
                ?, uuid, `order`, previous_problem, requirements,
                potential_failure_mode, potential_effect_of_failure, potential_cause_of_failure,
                controls_prevention, controls_detection, NOW(), NOW()
            FROM process_function_details
            WHERE header_id = ?
            ORDER BY `order`
        ", [$revisionHeaderId, $headerId]);
    }
}
