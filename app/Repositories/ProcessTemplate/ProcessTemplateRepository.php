<?php

namespace App\Repositories\ProcessTemplate;

use App\Models\ProcessDetail;
use App\Models\ProcessHeader;

class ProcessTemplateRepository
{
    public function storeHeader(array $headerData): ProcessHeader
    {
        return ProcessHeader::create($headerData);
    }

    public function storeDetails(ProcessHeader $processHeader, array $detailsData)
    {
        return $processHeader->details()->createMany($detailsData);
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
}
