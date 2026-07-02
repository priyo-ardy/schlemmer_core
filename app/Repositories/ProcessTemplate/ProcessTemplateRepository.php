<?php

namespace App\Repositories\ProcessTemplate;

use App\Models\ProcessChangeLogRevision;
use App\Models\ProcessDetail;
use App\Models\ProcessHeader;
use App\Models\ProcessHeaderRevision;
use Illuminate\Database\Eloquent\Collection;

class ProcessTemplateRepository
{
    public function getHeaderById(int $id): ProcessHeader
    {
        return ProcessHeader::find($id);
    }

    public function storeHeader(array $headerData): ProcessHeader
    {
        return ProcessHeader::create($headerData);
    }

    public function storeDetails(ProcessHeader $processHeader, array $detailsData)
    {
        return $processHeader->details()->createMany($detailsData);
    }

    public function getDetailData(int $idHeader): Collection
    {
        return ProcessDetail::where('header_id', $idHeader)->get();
    }

    public function findManyByIds(array $ids): Collection
    {
        return ProcessHeader::whereIn('id', $ids)->get();
    }

    public function updateHeader($id, $headerData)
    {
        $header = ProcessHeader::findOrFail($id);
        $header->update($headerData);
        return $header;
    }

    public function deleteDetailsNotIn($idHeader, array $keptIds)
    {
        // Menggunakan Eloquent Builder
        // Hapus yang header_id nya cocok, tapi id detailnya tidak ada di dalam daftar yang dipertahankan
        return ProcessDetail::where('header_id', $idHeader)
            ->whereNotIn('id', $keptIds)
            ->delete();
    }

    public function updateDetail($id, $data)
    {
        return ProcessDetail::where('id', $id)->update($data);
    }

    public function createDetail($data)
    {
        return ProcessDetail::create($data); // atau insert()
    }

    public function getLogs($headerId)
    {
        return ProcessChangeLogRevision::where('header_id', $headerId)
            ->with(['revisionHeader:id,revision,change_reason', 'creator:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getLogDetail($logId)
    {
        return ProcessHeaderRevision::with(['details' => function ($query) {
            $query->orderBy('order', 'asc'); // Sort di level detail
        }])
            ->where('id', $logId)
            ->first();
    }

    public function deleteAll($ids)
    {
        return ProcessHeader::whereIn('id', $ids)->delete();
    }

    public function upsertDetails(array $details)
    {
        if (empty($details)) return;

        return ProcessDetail::upsert($details, ['id'], [
            'order',
            'previous_problem',
            'requirements',
            'potential_failure_mode',
            'potential_effect_of_failure',
            'potential_cause_of_failure',
            'classification',
            'occurrence',
            'detection',
            'rpn',
            'recommended_action',
            'severity',
            'controls_prevention',
            'controls_detection',
            'responsibility',
            'target_completion_date',
            'action_taken_completion_date',
            'result_severity',
            'result_occurrence',
            'result_detection',
            'result_rpn',
            'updated_at'
        ]);
    }
}
