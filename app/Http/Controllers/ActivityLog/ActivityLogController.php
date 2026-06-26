<?php

namespace App\Http\Controllers\ActivityLog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString();

        $query = Activity::query()
            // Ambil kolom minimum agar hemat memory saat jutaan baris
            ->select([
                'id',
                'log_name',
                'description',
                'subject_type',
                'created_at',
            ])
            // Urutkan pakai index yang ada: created_at (+ tie-breaker id biar stabil)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->when($search !== '', function ($q) use ($search) {
                // Batasi panjang search untuk mencegah query LIKE yang terlalu liar
                $term = mb_substr($search, 0, 80);

                $q->where(function ($sub) use ($term) {
                    // Jika user input seperti "App\Models\User" maka cocokkan subject_type secara tepat
                    $sub->when(str_contains($term, '\\'), function ($q2) use ($term) {
                        $q2->orWhere('subject_type', $term);
                    });

                    // LIKE masih digunakan untuk description, tapi dibatasi input & dipaginate
                    $sub->orWhere('description', 'like', '%' . $term . '%');
                });
            });

        return Inertia::render('AuditLogs/Index', [
            'logs' => $query->paginate(50)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function purge(Request $request)
    {
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        $start = $validated['start'];
        $end = $validated['end'];

        // Hapus bertahap agar tidak membuat satu query delete raksasa
        // dan mengurangi lock/timeouts untuk jutaan data.
        $lastId = 0;

        while (true) {
            $ids = Activity::query()
                ->whereBetween('created_at', [$start, $end])
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit(2000)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $lastId = $ids->max();

            DB::table('activity_log')
                ->whereIn('id', $ids)
                ->delete();
        }

        return back()->with('success', 'Logs cleared!');
    }
}
