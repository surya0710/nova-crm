<?php

namespace App\Services\Export;

use App\Contracts\Export\ExportableEntityInterface;
use App\Jobs\ProcessExportSessionJob;
use App\Models\ExportSession;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Dashboard\ModuleSubscriptionService;
use App\Services\Export\Writers\CsvExportWriter;
use App\Services\Export\Writers\ExportWriterInterface;
use App\Services\Export\Writers\PdfExportWriter;
use App\Services\Export\Writers\XlsxExportWriter;
use App\Services\TenantContext;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ExportPlatformService
{
    public function __construct(
        protected ExportDefinitionRegistry $registry,
        protected ExportCatalogService $catalog,
        protected TenantContext $tenant,
        protected AuditLogger $auditLogger,
        protected ModuleSubscriptionService $modules,
    ) {}

    public function failStale(DateTimeInterface $before): int
    {
        return ExportSession::query()
            ->withoutGlobalScopes()
            ->whereIn('status', [ExportSession::STATUS_QUEUED, ExportSession::STATUS_RUNNING])
            ->where('updated_at', '<', $before)
            ->update([
                'status' => ExportSession::STATUS_FAILED,
                'last_error' => 'Export exceeded the stale queue work threshold.',
                'completed_at' => now(),
            ]);
    }

    /**
     * @param  array{mode?: string, ids?: list<int>, filters?: array<string, mixed>}  $selection
     * @param  list<string>|null  $columns
     */
    public function start(
        Organization $organization,
        User $actor,
        string $entityType,
        string $format,
        array $selection,
        ?array $columns = null,
    ): ExportSession {
        $format = strtolower($format);
        if (! array_key_exists($format, config('export.formats', []))) {
            throw ValidationException::withMessages([
                'format' => __('Unsupported export format.'),
            ]);
        }

        if (! $this->catalog->userCanAccessEntity($actor, $organization, $entityType)) {
            abort(403, 'You are not authorized to export this entity.');
        }

        $adapter = $this->registry->resolve($entityType);
        $this->assertEntityPermission($actor, $organization, $adapter);

        $resolvedColumns = $this->resolveColumns($adapter, $columns);
        $query = $adapter->resolveQuery($organization, $selection);

        $max = (int) config('export.max_records', 500000);
        $total = (clone $query)->limit($max + 1)->count();

        if ($total === 0) {
            throw ValidationException::withMessages([
                'selection' => __('No matching records were found for this export.'),
            ]);
        }

        if ($total > $max) {
            throw ValidationException::withMessages([
                'selection' => __('Export exceeds the maximum of :max records.', ['max' => $max]),
            ]);
        }

        if ($format === 'pdf' && $total > (int) config('export.pdf_max_rows', 2000)) {
            throw ValidationException::withMessages([
                'format' => __('PDF exports are limited to :max rows. Choose CSV or Excel instead.', [
                    'max' => config('export.pdf_max_rows', 2000),
                ]),
            ]);
        }

        $mode = $selection['mode'] ?? 'ids';
        $ids = null;
        if (in_array($mode, ['ids', 'page', 'selected'], true)) {
            $ids = array_values(array_unique(array_map('intval', $selection['ids'] ?? [])));
        }

        $session = ExportSession::query()->create([
            'organization_id' => $organization->id,
            'initiated_by' => $actor->id,
            'module' => $adapter->module(),
            'entity_type' => $entityType,
            'format' => $format,
            'selection_mode' => $mode,
            'status' => ExportSession::STATUS_PENDING,
            'total_count' => $total,
            'record_ids' => $ids,
            'filters' => $selection['filters'] ?? null,
            'columns' => $resolvedColumns,
            'metadata' => [
                'preset' => null,
            ],
            'disk' => config('export.disk', 'local'),
            'download_token' => Str::random(48),
            'download_expires_at' => now()->addHours((int) config('export.download_ttl_hours', 72)),
        ]);

        $this->auditLogger->log($session, 'export_started', [
            'entity_type' => $entityType,
            'format' => $format,
            'total_count' => $total,
            'selection_mode' => $mode,
        ], $actor);

        $threshold = (int) config('export.queue_threshold_rows', 100);

        if ($total > $threshold) {
            $session->forceFill(['status' => ExportSession::STATUS_QUEUED])->save();
            ProcessExportSessionJob::dispatch($session->id)->afterCommit();

            $this->auditLogger->log($session, 'export_queued', [
                'total_count' => $total,
            ], $actor);

            return $session->fresh();
        }

        return $this->process($session->fresh());
    }

    public function process(ExportSession $session, bool $finalizeFailure = true): ExportSession
    {
        if ($session->isTerminal()) {
            return $session;
        }

        $organization = Organization::query()->findOrFail($session->organization_id);
        $this->tenant->set($organization);

        $adapter = $this->registry->resolve($session->entity_type);
        $actor = User::query()->find($session->initiated_by);
        $columns = $session->columns ?? $adapter->defaultColumns();
        $headers = $this->headerLabels($adapter, $columns);

        $session->forceFill([
            'status' => ExportSession::STATUS_RUNNING,
            'started_at' => $session->started_at ?? now(),
            'last_error' => null,
        ])->save();

        $writer = $this->makeWriter($session->format);
        $selection = [
            'mode' => $session->selection_mode,
            'ids' => $session->record_ids ?? [],
            'filters' => $session->filters ?? [],
        ];

        try {
            $writer->begin($session, $organization, $actor, $headers);

            $query = $adapter->resolveQuery($organization, $selection);
            $eager = $adapter->eagerLoads($columns);
            if ($eager !== []) {
                $query->with($eager);
            }

            $chunkSize = max(1, (int) config('export.chunk_size', 250));
            $processed = 0;

            $query->orderBy($query->getModel()->getQualifiedKeyName())
                ->chunkById($chunkSize, function ($records) use ($adapter, $columns, $writer, $session, &$processed) {
                    foreach ($records as $record) {
                        $mapped = $adapter->mapRow($record, $columns);
                        $ordered = [];
                        foreach ($columns as $key) {
                            $ordered[] = $mapped[$key] ?? '';
                        }
                        $writer->writeRow($ordered);
                        $processed++;
                    }

                    $session->forceFill(['processed_count' => $processed])->save();
                });

            $file = $writer->finish();

            $session->forceFill([
                'status' => ExportSession::STATUS_COMPLETED,
                'processed_count' => $processed,
                'file_path' => $file['path'],
                'original_filename' => $file['filename'],
                'mime_type' => $file['mime'],
                'file_size' => $file['size'],
                'disk' => config('export.disk', 'local'),
                'completed_at' => now(),
            ])->save();

            $this->auditLogger->log($session, 'export_completed', [
                'entity_type' => $session->entity_type,
                'format' => $session->format,
                'record_count' => $processed,
                'file_size' => $file['size'],
                'duration_seconds' => $session->durationSeconds(),
            ], $actor);
        } catch (Throwable $e) {
            $session->forceFill([
                'status' => $finalizeFailure ? ExportSession::STATUS_FAILED : ExportSession::STATUS_QUEUED,
                'last_error' => $e->getMessage(),
                'completed_at' => $finalizeFailure ? now() : null,
            ])->save();

            if ($finalizeFailure) {
                $this->auditLogger->log($session, 'export_failed', [
                    'error' => $e->getMessage(),
                ], $actor);
            }

            throw $e;
        }

        return $session->fresh();
    }

    public function download(ExportSession $session, User $actor): StreamedResponse
    {
        if (! $session->isDownloadable()) {
            abort(404, 'Export file is not available.');
        }

        $disk = $session->disk ?: config('export.disk', 'local');
        $path = $session->file_path;

        $session->forceFill(['downloaded_at' => now()])->save();

        $this->auditLogger->log($session, 'export_downloaded', [
            'entity_type' => $session->entity_type,
            'format' => $session->format,
            'record_count' => $session->processed_count,
        ], $actor);

        return Storage::disk($disk)->download(
            $path,
            $session->original_filename ?: basename($path),
            ['Content-Type' => $session->mime_type ?: 'application/octet-stream']
        );
    }

    public function revoke(ExportSession $session, User $actor): ExportSession
    {
        $session->forceFill([
            'status' => ExportSession::STATUS_REVOKED,
            'revoked_at' => now(),
        ])->save();

        if ($session->file_path) {
            $disk = $session->disk ?: config('export.disk', 'local');
            Storage::disk($disk)->delete($session->file_path);
        }

        $this->auditLogger->log($session, 'export_revoked', [
            'entity_type' => $session->entity_type,
        ], $actor);

        return $session->fresh();
    }

    public function regenerate(ExportSession $session, User $actor): ExportSession
    {
        $organization = Organization::query()->findOrFail($session->organization_id);

        return $this->start(
            $organization,
            $actor,
            $session->entity_type,
            $session->format,
            [
                'mode' => $session->selection_mode,
                'ids' => $session->record_ids ?? [],
                'filters' => $session->filters ?? [],
            ],
            $session->columns,
        );
    }

    public function delete(ExportSession $session, User $actor): void
    {
        if ($session->file_path) {
            $disk = $session->disk ?: config('export.disk', 'local');
            Storage::disk($disk)->delete($session->file_path);
        }

        $this->auditLogger->log($session, 'export_deleted', [
            'entity_type' => $session->entity_type,
        ], $actor);

        $session->delete();
    }

    /**
     * Column catalog for listing / create UI.
     *
     * @return list<array<string, mixed>>
     */
    public function columnsFor(string $entityType): array
    {
        $adapter = $this->registry->resolve($entityType);

        return array_map(
            static fn (ExportColumnDefinition $col) => $col->toArray(),
            array_values(array_filter(
                $adapter->columnDefinitions(),
                static fn (ExportColumnDefinition $col) => ! $col->sensitive
            ))
        );
    }

    protected function makeWriter(string $format): ExportWriterInterface
    {
        return match ($format) {
            'csv' => new CsvExportWriter,
            'xlsx' => new XlsxExportWriter,
            'pdf' => new PdfExportWriter,
            default => throw ValidationException::withMessages([
                'format' => __('Unsupported export format.'),
            ]),
        };
    }

    /**
     * @param  list<string>|null  $requested
     * @return list<string>
     */
    protected function resolveColumns(ExportableEntityInterface $adapter, ?array $requested): array
    {
        $allowed = [];
        foreach ($adapter->columnDefinitions() as $col) {
            if (! $col->sensitive) {
                $allowed[$col->key] = $col;
            }
        }

        if ($requested === null || $requested === []) {
            return array_values(array_filter(
                $adapter->defaultColumns(),
                static fn (string $key) => isset($allowed[$key])
            ));
        }

        $resolved = [];
        foreach ($requested as $key) {
            if (isset($allowed[$key])) {
                $resolved[] = $key;
            }
        }

        if ($resolved === []) {
            throw ValidationException::withMessages([
                'columns' => __('Select at least one exportable column.'),
            ]);
        }

        return $resolved;
    }

    /**
     * @param  list<string>  $columns
     * @return list<string>
     */
    protected function headerLabels(ExportableEntityInterface $adapter, array $columns): array
    {
        $byKey = [];
        foreach ($adapter->columnDefinitions() as $col) {
            $byKey[$col->key] = $col->label;
        }

        return array_map(static fn (string $key) => $byKey[$key] ?? $key, $columns);
    }

    protected function assertEntityPermission(User $actor, Organization $organization, ExportableEntityInterface $adapter): void
    {
        if ($actor->is_super_admin || $actor->isOwnerOf($organization)) {
            return;
        }

        if ($actor->hasPermission('exports.manage', $organization)) {
            return;
        }

        if (! $actor->hasPermission($adapter->permission(), $organization)
            && ! $actor->hasPermission('exports.create', $organization)) {
            abort(403, 'You are not authorized to export this entity.');
        }
    }
}
