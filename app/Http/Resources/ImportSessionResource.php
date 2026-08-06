<?php

namespace App\Http\Resources;

use App\Models\ImportSession;
use App\Services\Queue\QueueHealthService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ImportSession */
class ImportSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $total = max(0, (int) $this->total_rows);
        $processed = max(0, (int) $this->processed_rows);
        $summary = is_array($this->validation_summary) ? $this->validation_summary : [];
        $queue = app(QueueHealthService::class)->snapshot();
        $active = in_array($this->status, [ImportSession::STATUS_QUEUED, ImportSession::STATUS_IMPORTING], true);

        return [
            'id' => $this->id,
            'entity_type' => $this->entity_type,
            'filename' => $this->original_filename,
            'status' => $this->status,
            'status_label' => str($this->status)->replace('_', ' ')->title()->toString(),
            'processed_rows' => $processed,
            'failed_rows' => (int) $this->failed_count,
            'skipped_rows' => (int) $this->skipped_count,
            'total_rows' => $total,
            'progress_percentage' => $total === 0 ? 0 : min(100, (int) round($processed / $total * 100)),
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->completed_at?->toIso8601String(),
            'duration' => $this->started_at && $this->completed_at ? $this->started_at->diffInSeconds($this->completed_at) : null,
            'fatal_reason' => $this->status === ImportSession::STATUS_FAILED ? $this->last_error : null,
            'worker_offline' => $active && ($queue['pending'] ?? 0) > 0 && ($queue['workers']['active'] ?? 0) === 0,
            'stalled' => $active && $this->updated_at->lt(now()->subSeconds((int) config('import.stale_after_seconds', 900))),
            'created_rows' => (int) $this->created_count,
            'updated_rows' => (int) $this->updated_count,
            'row_errors' => $summary['execution_errors'] ?? $summary['errors'] ?? [],
            'uploader' => $this->whenLoaded('uploader'),
        ];
    }
}
