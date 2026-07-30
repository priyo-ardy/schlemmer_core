<?php

namespace App\Jobs;

use App\Models\ApprovalSetup;
use App\Models\ChangeLogs;
use App\Models\PfmeaHeader;
use App\Services\ChangeLogs\ChangeLogsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class ProcessFunctionQueueJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    protected string $action;
    protected array $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(string $action, array $payload)
    {
        $this->action = $action;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(ChangeLogsService $logService): void
    {
        match ($this->action) {
            'create' => $this->handleCreate($logService),
            'update' => $this->handleUpdate($logService),
            'delete' => $this->handleDelete($logService),
            default => Log::warning("Action {$this->action} undefined")
        };
    }

    private function handleCreate(): void {}

    private function handleUpdate(ChangeLogsService $logService): void
    {
        $processId  = $this->payload['id'] ?? null;
        $reason     = $this->payload['reason'] ?? 'System auto update on Process Function changes';
        $oldDetails = $this->payload['old_details'] ?? [];
        $newDetail  = $this->payload['new_detail'] ?? [];

        if (!$processId) {
            Log::warning("ProcessFunctionQueueJob [update]: Process ID tidak ditemukan di payload.", $this->payload);
            return;
        }

        $pfmeaIds = DB::table('pfmea_details')
            ->where('process_id', $processId)
            ->whereNotNull('pfmea_id')
            ->pluck('pfmea_id')
            ->unique()
            ->toArray();

        if (empty($pfmeaIds)) {
            Log::info("ProcessFunctionQueueJob [update]: Tidak ada dokumen PFMEA yang terhubung dengan process_id: {$processId}");
            return;
        }

        foreach ($pfmeaIds as $pfmeaId) {
            // Ambil instance Model PFMEA
            $pfmea = PfmeaHeader::find($pfmeaId);

            if (!$pfmea) {
                continue;
            }

            $beforeData = [
                'header'  => $pfmea->toArray(),
                'details' => $oldDetails,
            ];

            $pfmea->increment('revision', 1, [
                'updated_at' => now(),
            ]);

            $afterData = [
                'header'  => $pfmea->fresh()->toArray(),
                'details' => $newDetail,
            ];

            $logService->store(
                $pfmea,
                'update',
                $reason,
                $beforeData,
                $afterData
            );
        }

        Log::info("ProcessFunctionQueueJob [update]: Berhasil me-increment revision (+1) untuk " . count($pfmeaIds) . " PFMEA (ID: " . implode(', ', $pfmeaIds) . ")");
    }

    private function handleDelete(): void {}

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessFunctionQueueJob GAGAL pada aksi {$this->action}. Pesan: " . $exception->getMessage());
        activity('process_function_job')
            ->causedBy(0)
            ->withProperties([])
            ->log('Job failed: Failed to execute process function job data');
    }
}
