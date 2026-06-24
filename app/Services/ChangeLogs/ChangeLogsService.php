<?php

namespace App\Services\ChangeLogs;

use App\Models\ChangeLogs;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ChangeLogsService
{
    public function store(Model $model, string $eventType, string $reason, ?array $old, ?array $new)
    {
        ChangeLogs::create([
            'user_id' => Auth::id() ?? 0,
            'table_name' => $model->getTable(),
            'item_id' => $model->getKey(),
            'event_name' => $eventType,
            'revision' => isset($model->revision) ? $model->revision : 0,
            'change_reason' => $reason,
            'before' => $old ?? null,
            'after' => $new ?? null,
            'created_by' => Auth::id()
        ]);
    }

    public function getLogsData(int $id, string $table_name): Collection
    {
        return ChangeLogs::with('creator')
            ->where('item_id', $id)
            ->where('table_name', $table_name)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
