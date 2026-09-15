<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\PFMEA\PfmeaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    public function __construct(
        protected PfmeaService $service
    ) {}

    public function export(Request $request)
    {
        try {
            $validated = $request->validate([
                'project_id'  => ['required', 'integer', 'exists:projects,id'],
                'material_id' => ['required', 'integer', 'exists:materials,id']
            ]);

            $export = $this->service->getPfmeaExportedData($validated);

            if (!$export['success'] || empty($export['data'])) {
                return redirect()->back()->with('error', $export['message'] ?? 'Data tidak ditemukan.');
            }

            $pfmea = $export['data'];

            if (!empty($pfmea['revision_history'])) {
                $pfmea['revision_history'] = array_slice($pfmea['revision_history'], -3);
            }

            // Cukup kirim array $items murni (flat)
            $pdf = Pdf::loadView('pfmea.pfmea-export', [
                'pfmea' => $pfmea,
                'items' => $pfmea['items'] ?? [],
            ])->setPaper('a4', 'landscape');

            return $pdf->stream("PFMEA-{$pfmea['doc_no']}.pdf");
        } catch (\Exception $e) {
            Log::error('Export failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate PDF for selected PFMEA document.');
        }
    }

    private function prepareGroupedItems(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $step = $item['process_step'] ?? '-';
            $grouped[$step][] = $item;
        }

        foreach ($grouped as $step => &$stepItems) {
            $controlCounts = [];
            $total = count($stepItems);

            for ($i = 0; $i < $total; $i++) {
                $currentControl = $stepItems[$i]['control_detection'] ?? '';
                if ($i > 0 && $currentControl === ($stepItems[$i - 1]['control_detection'] ?? '')) {
                    $controlCounts[$i] = 0;
                    $parent = $i - 1;
                    while ($parent >= 0 && ($stepItems[$parent]['control_detection'] ?? '') === $currentControl) {
                        $parent--;
                    }
                    $parent++;
                    $controlCounts[$parent] = ($controlCounts[$parent] ?? 1) + 1;
                } else {
                    $controlCounts[$i] = 1;
                }
            }

            foreach ($stepItems as $i => &$item) {
                $item['rowspanControl'] = $controlCounts[$i] ?? 1;
            }
        }

        return $grouped;
    }
}
