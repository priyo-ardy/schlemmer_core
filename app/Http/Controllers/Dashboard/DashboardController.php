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

            // --- FUNGSI HELPER UNTUK MENGUBAH URL GAMBAR KE PATH LOKAL ---
            $fixImagePath = function ($htmlContent) {
                if (empty($htmlContent) || !is_string($htmlContent)) return $htmlContent;

                // Mencari tag src yang mengarah ke folder storage dan menggantinya dengan public_path()
                return preg_replace_callback('/src="([^"]*\/storage\/([^"]+))"/', function ($matches) {
                    $localPath = public_path('storage/' . $matches[2]);
                    return 'src="' . $localPath . '"';
                }, $htmlContent);
            };

            // Terapkan replacer ke seluruh field string di dalam items worksheet
            if (!empty($pfmea['items'])) {
                foreach ($pfmea['items'] as &$item) {
                    foreach ($item as $key => $value) {
                        if (is_string($value)) {
                            $item[$key] = $fixImagePath($value);
                        }
                    }
                }
                unset($item);
            }

            // Terapkan replacer ke revision history
            if (!empty($pfmea['revision_history'])) {
                foreach ($pfmea['revision_history'] as &$rev) {
                    if (isset($rev['content'])) {
                        $rev['content'] = $fixImagePath($rev['content']);
                    }
                }
                unset($rev);
            }
            // -------------------------------------------------------------

            // Kirim data ke view PDF
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
