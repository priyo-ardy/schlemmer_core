<?php

namespace App\Repositories\ProcessTemplate;

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
}
