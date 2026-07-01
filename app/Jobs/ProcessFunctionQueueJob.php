<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
    public function handle(): void
    {
        match ($this->action) {
            'create' => '',
            'update' => '',
            'delete' => '',
            default => Log::warning("Action {$this->action} undefined")
        };
    }

    private function handleCreate(): void {}

    private function handleUpdate(): void {}

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
