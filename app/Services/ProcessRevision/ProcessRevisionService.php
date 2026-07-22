<?php

namespace App\Services\ProcessRevision;

use App\Models\ProcessHeader;
use App\Repositories\ProcessRevision\ProcessRevisionRepositories;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProcessRevisionService
{
    protected $revisionRepo;

    public function __construct(ProcessRevisionRepositories $revisionRepo)
    {
        $this->revisionRepo = $revisionRepo;
    }

    public function createSnapShot(ProcessHeader $header, string $action, ?string $reason = null): void
    {
        $revisionHeader = $this->revisionRepo->storeHeader([
            'uuid' => Str::uuid7(),
            'header_id' => $header->id,
            'sequence' => $header->sequence,
            'process_parent' => $header->process_parent,
            'process_child' => $header->process_child,
            'revision' => $header->revision,
            'name' => $header->name,
            'remark' => $header->remark,
            'change_reason' => $reason,
            'created_by' => Auth::id()
        ]);


        $this->revisionRepo->copyDetailsToRevision($header->id, $revisionHeader->id);

        $this->revisionRepo->storeLog([
            'header_id' => $header->id,
            'revision' => $header->revision,
            'action' => $action,
            'change_reason' => $reason,
            'created_by' => Auth::id()
        ]);
    }
}
