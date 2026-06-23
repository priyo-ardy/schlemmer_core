<?php

namespace App\Services\GenerateCode;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AutoNumberService
{
    public function generate(string $key): string
    {
        try {
            return DB::transaction(function () use ($key) {
                $counter = DB::table('sys_counters')
                    ->where('key', $key)
                    ->lockForUpdate()
                    ->first();

                if (! $counter) {
                    Log::error("AutoNumberService: Counter key '{$key}' tidak ditemukan di sys_counters.");
                    activity('generate_code')
                        ->causedBy(Auth::id())
                        ->withProperties([
                            'table' => $key,
                            'ip' => Request::ip(),
                        ])
                        ->log("Generate failed: Failed to generate auto code,  System counter configuration missing for key: {$key}");

                    throw new \Exception("System counter configuration missing for key: {$key}");
                }

                $nextSequence = $counter->last_sequence + 1;
                $paddedSequence = str_pad($nextSequence, $counter->sequence_length, '0', \STR_PAD_LEFT);
                $now = Carbon::now();

                $generatedCode = $counter->format;
                $generatedCode = str_replace('{PREFIX}', $counter->prefix, $generatedCode);
                $generatedCode = str_replace('{YEAR}', $now->format('Y'), $generatedCode);
                $generatedCode = str_replace('{YEAR_SHORT}', $now->format('y'), $generatedCode);
                $generatedCode = str_replace('{MONTH}', $now->format('m'), $generatedCode);
                $generatedCode = str_replace('{SEQUENCE}', $paddedSequence, $generatedCode);

                DB::table('sys_counters')
                    ->where('key', $key)
                    ->update([
                        'last_sequence' => $nextSequence,
                        'updated_at' => Carbon::now(),
                    ]);

                return $generatedCode;
            });
        } catch (\Exception $e) {
            Log::error('AutoNumberService Error: '.$e->getMessage());
            activity('generate_code')
                ->causedBy(Auth::id())
                ->withProperties([
                    'table' => $key,
                    'ip' => Request::ip(),
                ])
                ->log('Generate failed: Failed to generate auto code, Error: '.$e->getMessage());
            throw $e;
        }
    }
}
