<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportableEntityInterface;
use App\Jobs\ProcessImportSessionJob;
use App\Models\ImportSession;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Import Platform orchestration service.
 *
 * Owns upload, parsing, column detection, validation, preview, error reporting,
 * and import session lifecycle. Contains no CRM entity persistence logic.
 */
class ImportPlatformService
{
    public function __construct(
        protected ImportEntityRegistry $registry,
        protected SpreadsheetReader $reader,
        protected ColumnDetector $columnDetector,
        protected ImportValidationEngine $validator,
        protected ImportErrorReportGenerator $errorReportGenerator,
        protected AuditLogger $auditLogger,
    ) {}

    public function registry(): ImportEntityRegistry
    {
        return $this->registry;
    }

    /**
     * Upload a spreadsheet and create an import session in Uploaded status.
     */
    public function upload(
        Organization $organization,
        string $entityType,
        UploadedFile $file,
        ?User $user = null,
        ?string $worksheetName = null,
    ): ImportSession {
        $this->assertRegisteredEntity($entityType);
        $this->assertSupportedUpload($file);

        $disk = (string) config('import.disk', 'local');
        $directory = 'imports/'.$organization->id.'/'.$entityType;
        $path = $file->store($directory, $disk);

        if ($path === false) {
            throw new RuntimeException('Unable to store import upload.');
        }

        return DB::transaction(function () use ($organization, $entityType, $file, $user, $path, $worksheetName, $disk) {
            $session = ImportSession::query()->create([
                'organization_id' => $organization->id,
                'entity_type' => $entityType,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'disk' => $disk,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $user?->id,
                'status' => ImportSession::STATUS_UPLOADED,
                'worksheet_name' => $worksheetName,
                'started_at' => now(),
            ]);

            $this->auditLogger->log($session, 'uploaded', [
                'entity_type' => $entityType,
                'filename' => $session->original_filename,
                'file_size' => $session->file_size,
            ], $user);

            return $session->fresh();
        });
    }

    /**
     * Parse, map, and validate an uploaded session. Transitions to Ready or Failed.
     */
    public function validate(ImportSession $session, ?User $user = null): ImportSession
    {
        $this->assertOrganizationOwned($session);
        $entity = $this->assertRegisteredEntity($session->entity_type);

        if ($session->status !== ImportSession::STATUS_UPLOADED) {
            throw new InvalidArgumentException(
                "Import session must be in uploaded status to validate, got [{$session->status}]."
            );
        }

        $this->transition($session, ImportSession::STATUS_VALIDATING);

        try {
            $absolutePath = $this->absolutePath($session);
            $parsed = $this->reader->read($absolutePath, $session->worksheet_name);
            $detection = $this->columnDetector->detect($parsed->headers, $entity->fieldDefinitions());
            $mapping = $this->resolveColumnMapping($session->column_mapping, $detection['mapping']);
            $unknownColumns = $this->unknownColumnsForMapping($parsed->headers, $mapping);
            $unmappedRequired = $this->unmappedRequiredFields($entity->fieldDefinitions(), $mapping);

            $result = $this->validator->validate(
                $parsed,
                $entity->fieldDefinitions(),
                $mapping,
                $unknownColumns,
                $detection['duplicate_columns'],
            );
            $result = $this->validator->mergeEntityErrors(
                $result,
                $entity->validateMappedRows($result['preview_rows'], $session),
            );

            $duplicateRows = collect($result['errors'])
                ->filter(fn (array $error) => str_contains(strtolower($error['error']), 'duplicate'))
                ->pluck('row_number')
                ->unique()
                ->count();

            $this->transition($session, ImportSession::STATUS_READY, [
                'worksheet_name' => $parsed->activeWorksheet,
                'detected_headers' => $parsed->headers,
                'column_mapping' => $mapping,
                'total_rows' => $result['total_rows'],
                'failed_count' => $result['invalid_rows'],
                'validation_summary' => [
                    'valid_rows' => $result['valid_rows'],
                    'invalid_rows' => $result['invalid_rows'],
                    'duplicate_rows' => $duplicateRows,
                    'unknown_columns' => $result['unknown_columns'],
                    'duplicate_columns' => $result['duplicate_columns'],
                    'unmapped_required' => $unmappedRequired,
                    'errors' => $result['errors'],
                ],
                'last_error' => null,
            ]);

            $this->auditLogger->log($session, 'validated', [
                'entity_type' => $session->entity_type,
                'total_rows' => $result['total_rows'],
                'valid_rows' => $result['valid_rows'],
                'invalid_rows' => $result['invalid_rows'],
                'unknown_columns' => $result['unknown_columns'],
                'duplicate_columns' => $result['duplicate_columns'],
            ], $user);

            return $session->fresh();
        } catch (\Throwable $e) {
            if ($session->status === ImportSession::STATUS_VALIDATING) {
                $this->transition($session, ImportSession::STATUS_FAILED, [
                    'last_error' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            $this->auditLogger->log($session, 'validation_failed', [
                'entity_type' => $session->entity_type,
                'error' => $e->getMessage(),
            ], $user);

            throw $e;
        }
    }

    /**
     * Build a preview payload from the validated session (recomputes from file).
     */
    public function preview(ImportSession $session, ?User $user = null): ImportPreview
    {
        $this->assertOrganizationOwned($session);
        $entity = $this->assertRegisteredEntity($session->entity_type);

        if (! in_array($session->status, [
            ImportSession::STATUS_READY,
            ImportSession::STATUS_VALIDATING,
            ImportSession::STATUS_UPLOADED,
        ], true)) {
            throw new InvalidArgumentException(
                "Import session preview requires uploaded, validating, or ready status, got [{$session->status}]."
            );
        }

        // Ensure validation artifacts exist.
        if ($session->status === ImportSession::STATUS_UPLOADED) {
            $session = $this->validate($session, $user);
        }

        $absolutePath = $this->absolutePath($session);
        $parsed = $this->reader->read($absolutePath, $session->worksheet_name);
        $fields = $entity->fieldDefinitions();
        $mapping = $session->column_mapping ?? $this->columnDetector->detect($parsed->headers, $fields)['mapping'];
        $detection = $this->columnDetector->detect($parsed->headers, $fields);
        $result = $this->validator->validate(
            $parsed,
            $fields,
            $mapping,
            $detection['unknown_columns'],
            $detection['duplicate_columns'],
        );
        $result = $this->validator->mergeEntityErrors(
            $result,
            $entity->validateMappedRows($result['preview_rows'], $session),
        );

        $preview = new ImportPreview(
            detectedColumns: $parsed->headers,
            mappedFields: $mapping,
            validRows: $result['valid_rows'],
            invalidRows: $result['invalid_rows'],
            totalRows: $result['total_rows'],
            rows: $result['preview_rows'],
            errors: $result['errors'],
            unknownColumns: $result['unknown_columns'],
            duplicateColumns: $result['duplicate_columns'],
            worksheetName: $parsed->activeWorksheet,
        );

        $this->auditLogger->log($session, 'preview_generated', [
            'entity_type' => $session->entity_type,
            'total_rows' => $preview->totalRows,
            'valid_rows' => $preview->validRows,
            'invalid_rows' => $preview->invalidRows,
        ], $user);

        return $preview;
    }

    /**
     * Stream a CSV error report for the session's latest validation errors.
     */
    public function errorReport(ImportSession $session): StreamedResponse
    {
        $this->assertOrganizationOwned($session);

        $errors = $this->combinedSessionErrors($session);

        if ($errors === null) {
            $preview = $this->preview($session);
            $errors = $preview->errors;
        }

        $filename = 'import-errors-'.$session->id.'-'.now()->format('Ymd-His').'.csv';
        $csv = $this->errorReportGenerator->toCsvString($errors);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Generate error report CSV contents without streaming (for tests / callers).
     */
    public function errorReportCsv(ImportSession $session): string
    {
        $this->assertOrganizationOwned($session);

        $errors = $this->combinedSessionErrors($session);

        if ($errors === null) {
            $preview = $this->preview($session);
            $errors = $preview->errors;
        }

        return $this->errorReportGenerator->toCsvString($errors);
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    protected function combinedSessionErrors(ImportSession $session): ?array
    {
        $summary = $session->validation_summary;
        if (! is_array($summary)) {
            return null;
        }

        return array_values(array_merge(
            is_array($summary['errors'] ?? null) ? $summary['errors'] : [],
            is_array($summary['execution_errors'] ?? null) ? $summary['execution_errors'] : [],
        ));
    }

    /**
     * Apply a user-supplied column mapping and re-validate.
     *
     * @param  array<string, string|null>  $mapping  field_key => header
     */
    public function applyMapping(ImportSession $session, array $mapping, ?User $user = null): ImportSession
    {
        $this->assertOrganizationOwned($session);

        if (! in_array($session->status, [ImportSession::STATUS_READY, ImportSession::STATUS_UPLOADED, ImportSession::STATUS_FAILED], true)) {
            throw new InvalidArgumentException(
                "Cannot remap import session in status [{$session->status}]."
            );
        }

        $session->forceFill([
            'column_mapping' => $mapping,
            'status' => ImportSession::STATUS_UPLOADED,
            'last_error' => null,
            'completed_at' => null,
        ])->save();

        $this->auditLogger->log($session, 'mapping_updated', [
            'entity_type' => $session->entity_type,
            'mapped_fields' => array_keys(array_filter($mapping)),
        ], $user);

        return $this->validate($session->fresh(), $user);
    }

    /**
     * Queue large imports; execute small ones synchronously.
     *
     * @param  array{duplicate_strategy?: string}  $options
     */
    public function startImport(ImportSession $session, ?User $user = null, array $options = []): ImportSession
    {
        $this->assertOrganizationOwned($session);

        if ($session->status !== ImportSession::STATUS_READY) {
            throw new InvalidArgumentException(
                "Import session must be in ready status to start, got [{$session->status}]."
            );
        }

        if (! empty($options['duplicate_strategy'])) {
            $metadata = $session->metadata ?? [];
            $metadata['duplicate_strategy'] = $options['duplicate_strategy'];
            $session->forceFill(['metadata' => $metadata])->save();
        }

        $threshold = (int) config('import.queue_threshold_rows', 100);

        if (($session->total_rows ?? 0) > $threshold) {
            $this->transition($session, ImportSession::STATUS_QUEUED);

            try {
                ProcessImportSessionJob::dispatch($session->id, $user?->id);
            } catch (Throwable $e) {
                $this->transition($session, ImportSession::STATUS_FAILED, [
                    'last_error' => 'Import could not be queued: '.$e->getMessage(),
                    'completed_at' => now(),
                ]);

                Log::error('import.queue.failed', $this->importLogContext($session, $user, [
                    'exception_class' => $e::class,
                    'exception' => $e,
                    'reason' => $e->getMessage(),
                ]));

                throw $e;
            }

            $this->auditLogger->log($session, 'import_queued', [
                'entity_type' => $session->entity_type,
                'total_rows' => $session->total_rows,
            ], $user);

            Log::info('import.execution.queued', $this->importLogContext($session, $user, [
                'rows_parsed' => $session->total_rows,
            ]));

            return $session->fresh();
        }

        return $this->executeImport($session, $user);
    }

    /**
     * Execute import for a ready session by invoking the entity adapter persistRow callback.
     */
    public function executeImport(ImportSession $session, ?User $user = null): ImportSession
    {
        $this->assertOrganizationOwned($session);
        $entity = $this->assertRegisteredEntity($session->entity_type);

        if (! in_array($session->status, [ImportSession::STATUS_READY, ImportSession::STATUS_QUEUED], true)) {
            throw new InvalidArgumentException(
                "Import session must be ready or queued to execute, got [{$session->status}]."
            );
        }

        $this->transition($session, ImportSession::STATUS_IMPORTING);

        $executionStartedAt = microtime(true);
        $memoryAtStart = memory_get_usage(true);
        $leadCountBefore = $session->entity_type === 'lead'
            ? Lead::query()->where('organization_id', $session->organization_id)->count()
            : null;

        $this->auditLogger->log($session, 'import_started', [
            'entity_type' => $session->entity_type,
            'total_rows' => $session->total_rows,
        ], $user);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $processed = 0;
        $rowErrors = [];
        $createdIds = [];
        $rowOutcomes = [];
        $validationErrorCount = 0;
        $databaseErrorCount = 0;
        $ownerResolutionErrorCount = 0;

        try {
            $absolutePath = $this->absolutePath($session);
            $parsed = $this->reader->read($absolutePath, $session->worksheet_name);
            $fields = $entity->fieldDefinitions();
            $mapping = $session->column_mapping
                ?? $this->columnDetector->detect($parsed->headers, $fields)['mapping'];
            $detection = $this->columnDetector->detect($parsed->headers, $fields);
            $result = $this->validator->validate(
                $parsed,
                $fields,
                $mapping,
                $detection['unknown_columns'],
                $detection['duplicate_columns'],
            );
            $result = $this->validator->mergeEntityErrors(
                $result,
                $entity->validateMappedRows($result['preview_rows'], $session),
            );

            Log::info('import.execution.started', $this->importLogContext($session, $user, [
                'rows_parsed' => $result['total_rows'],
                'rows_valid' => $result['valid_rows'],
                'rows_invalid' => $result['invalid_rows'],
                'memory_bytes' => $memoryAtStart,
            ]));

            foreach ($result['preview_rows'] as $row) {
                $processed++;
                $rowContext = $this->rowLogContext($session, $user, $row);

                if (! $row['valid']) {
                    $isDuplicate = collect($row['errors'])->contains(
                        fn (string $message) => str_contains(strtolower($message), 'duplicate')
                    );

                    if ($isDuplicate) {
                        $skipped++;
                    } else {
                        $failed++;
                    }

                    $validationErrorCount += count($row['errors']);
                    foreach ($this->errorsForRow($result['errors'], (int) $row['row_number']) as $error) {
                        $message = (string) $error['error'];
                        if (str_contains(strtolower($message), 'owner')) {
                            $ownerResolutionErrorCount++;
                        }

                        $rowErrors[] = [
                            'row_number' => $row['row_number'],
                            'column' => $error['column'] ?? null,
                            'field' => $error['field'] ?? null,
                            'error' => $message,
                            'value' => $error['value'] ?? null,
                            'category' => $isDuplicate ? 'duplicate' : 'validation',
                        ];
                    }

                    $rowOutcomes[] = [
                        'row_number' => $row['row_number'],
                        'result' => $isDuplicate ? 'skipped' : 'failed',
                        'reason' => implode('; ', $row['errors']),
                    ];
                    Log::warning('import.row.rejected', array_merge($rowContext, [
                        'validation_result' => 'invalid',
                        'insert_result' => $isDuplicate ? 'skipped' : 'not_attempted',
                        'reason' => implode('; ', $row['errors']),
                    ]));

                    continue;
                }

                try {
                    $outcome = $entity->persistRow($row['values'], $session);
                    $action = $outcome['action'] ?? 'created';

                    match ($action) {
                        'updated' => $updated++,
                        'skipped' => $skipped++,
                        default => $created++,
                    };

                    if (in_array($action, ['created', 'updated'], true) && isset($outcome['id'])) {
                        $createdIds[] = $outcome['id'];
                    }

                    Log::info('import.row.processed', array_merge($rowContext, [
                        'validation_result' => 'valid',
                        'insert_result' => $action,
                        'entity_id' => $outcome['id'] ?? null,
                        'organization_id_result' => $outcome['organization_id'] ?? $session->organization_id,
                        'assigned_user_id' => $outcome['assigned_to'] ?? null,
                        'created_by' => $outcome['created_by'] ?? null,
                        'owner_resolution' => $outcome['owner_resolution'] ?? null,
                        'reason' => $action === 'skipped' ? 'Adapter skipped the row.' : null,
                    ]));
                } catch (Throwable $e) {
                    $failed++;
                    $category = $this->exceptionCategory($e);
                    match ($category) {
                        'owner_resolution' => $ownerResolutionErrorCount++,
                        'validation' => $validationErrorCount++,
                        default => $databaseErrorCount++,
                    };

                    $rowErrors[] = [
                        'row_number' => $row['row_number'],
                        'column' => null,
                        'field' => null,
                        'error' => $e->getMessage(),
                        'value' => $row['values']['email'] ?? $row['values']['phone'] ?? null,
                        'category' => $category,
                    ];
                    $rowOutcomes[] = [
                        'row_number' => $row['row_number'],
                        'result' => 'failed',
                        'reason' => $e->getMessage(),
                        'category' => $category,
                    ];

                    Log::error('import.row.failed', array_merge($rowContext, [
                        'validation_result' => 'valid',
                        'insert_result' => 'failed',
                        'exception_class' => $e::class,
                        'exception' => $e,
                        'reason' => $e->getMessage(),
                        'category' => $category,
                    ]));
                }

                if ($processed % max(1, (int) config('import.chunk_size', 100)) === 0) {
                    $session->forceFill([
                        'processed_rows' => $processed,
                        'created_count' => $created,
                        'updated_count' => $updated,
                        'skipped_count' => $skipped,
                        'failed_count' => $failed,
                    ])->save();
                }
            }

            $durationMs = (int) round((microtime(true) - $executionStartedAt) * 1000);
            $memoryBytes = memory_get_peak_usage(true);
            $imported = $created + $updated;
            $leadCountAfter = $session->entity_type === 'lead'
                ? Lead::query()->where('organization_id', $session->organization_id)->count()
                : null;
            $zeroImportReason = $processed > 0 && $imported === 0
                ? "Import processed {$processed} rows but created or updated no records. Review row errors."
                : null;

            $summary = $session->validation_summary ?? [];
            $summary['execution_errors'] = $rowErrors;
            $summary['execution_summary'] = [
                'rows_parsed' => $result['total_rows'],
                'rows_valid' => $result['valid_rows'],
                'rows_invalid' => $result['invalid_rows'],
                'rows_processed' => $processed,
                'rows_imported' => $imported,
                'rows_failed' => $failed,
                'rows_skipped' => $skipped,
                'duplicate_count' => (int) ($summary['duplicate_rows'] ?? 0),
                'validation_errors' => $validationErrorCount,
                'database_errors' => $databaseErrorCount,
                'owner_resolution_errors' => $ownerResolutionErrorCount,
                'processing_time_ms' => $durationMs,
                'memory_start_bytes' => $memoryAtStart,
                'memory_peak_bytes' => $memoryBytes,
                'lead_count_before' => $leadCountBefore,
                'lead_count_after' => $leadCountAfter,
                'created_ids' => $createdIds,
                'row_outcomes' => $rowOutcomes,
            ];

            $this->transition($session, ImportSession::STATUS_COMPLETED, [
                'processed_rows' => $processed,
                'created_count' => $created,
                'updated_count' => $updated,
                'skipped_count' => $skipped,
                'failed_count' => $failed,
                'completed_at' => now(),
                'validation_summary' => $summary,
                'last_error' => $zeroImportReason,
            ]);

            $this->auditLogger->log($session, 'import_completed', [
                'entity_type' => $session->entity_type,
                'processed_rows' => $processed,
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'failed' => $failed,
                'duplicate_rows' => $summary['duplicate_rows'] ?? 0,
                'duration_ms' => $durationMs,
                'memory_peak_bytes' => $memoryBytes,
                'created_ids' => $createdIds,
            ], $user);

            $completionContext = $this->importLogContext($session, $user, [
                ...$summary['execution_summary'],
                'status' => ImportSession::STATUS_COMPLETED,
                'zero_import_reason' => $zeroImportReason,
            ]);
            if ($zeroImportReason !== null) {
                Log::warning('import.execution.zero_records', $completionContext);
            } else {
                Log::info('import.execution.completed', $completionContext);
            }

            return $session->fresh();
        } catch (Throwable $e) {
            $durationMs = (int) round((microtime(true) - $executionStartedAt) * 1000);
            if ($session->status === ImportSession::STATUS_IMPORTING) {
                $this->transition($session, ImportSession::STATUS_FAILED, [
                    'last_error' => $e->getMessage(),
                    'completed_at' => now(),
                    'processed_rows' => $processed,
                    'created_count' => $created,
                    'updated_count' => $updated,
                    'skipped_count' => $skipped,
                    'failed_count' => $failed,
                ]);
            }

            $this->auditLogger->log($session, 'import_failed', [
                'entity_type' => $session->entity_type,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ], $user);

            Log::critical('import.execution.failed', $this->importLogContext($session, $user, [
                'rows_processed' => $processed,
                'rows_imported' => $created + $updated,
                'rows_failed' => $failed,
                'rows_skipped' => $skipped,
                'duration_ms' => $durationMs,
                'memory_peak_bytes' => memory_get_peak_usage(true),
                'exception_class' => $e::class,
                'exception' => $e,
                'reason' => $e->getMessage(),
            ]));

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function importLogContext(ImportSession $session, ?User $user, array $extra = []): array
    {
        return array_merge([
            'import_id' => $session->id,
            'entity_type' => $session->entity_type,
            'organization_id' => $session->organization_id,
            'user_id' => $user?->id ?? $session->uploaded_by,
            'filename' => $session->original_filename,
        ], $extra);
    }

    /**
     * @param  array{row_number: int, values: array<string, mixed>, valid: bool, errors: list<string>}  $row
     * @return array<string, mixed>
     */
    protected function rowLogContext(ImportSession $session, ?User $user, array $row): array
    {
        return $this->importLogContext($session, $user, [
            'row_number' => $row['row_number'],
            'phone' => $row['values']['phone'] ?? null,
            'email' => $row['values']['email'] ?? null,
            'owner' => $row['values']['owner'] ?? null,
            'lead_status' => $row['values']['status'] ?? null,
        ]);
    }

    /**
     * @param  list<array{row_number: int, column: string|null, field: string|null, error: string, value: string|null}>  $errors
     * @return list<array{row_number: int, column: string|null, field: string|null, error: string, value: string|null}>
     */
    protected function errorsForRow(array $errors, int $rowNumber): array
    {
        return array_values(array_filter(
            $errors,
            static fn (array $error): bool => (int) $error['row_number'] === $rowNumber,
        ));
    }

    protected function exceptionCategory(Throwable $exception): string
    {
        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'owner')) {
            return 'owner_resolution';
        }

        if ($exception instanceof ValidationException
            || $exception instanceof InvalidArgumentException) {
            return 'validation';
        }

        return 'database';
    }

    public function cancel(ImportSession $session, ?User $user = null): ImportSession
    {
        $this->assertOrganizationOwned($session);

        if ($session->isTerminal()) {
            throw new InvalidArgumentException(
                "Import session [{$session->id}] is already terminal with status [{$session->status}]."
            );
        }

        $this->transition($session, ImportSession::STATUS_CANCELLED, [
            'completed_at' => now(),
        ]);

        $this->auditLogger->log($session, 'cancelled', [
            'entity_type' => $session->entity_type,
        ], $user);

        return $session->fresh();
    }

    /**
     * Find a session belonging to the given organization (tenant isolation).
     */
    public function findForOrganization(Organization $organization, int $sessionId): ?ImportSession
    {
        return ImportSession::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->whereKey($sessionId)
            ->first();
    }

    /**
     * @return list<ImportFieldDefinition>
     */
    public function fieldDefinitionsFor(string $entityType): array
    {
        return $this->assertRegisteredEntity($entityType)->fieldDefinitions();
    }

    protected function transition(ImportSession $session, string $to, array $attributes = []): void
    {
        ImportSession::assertValidTransition($session->status, $to);

        $session->forceFill(array_merge($attributes, [
            'status' => $to,
        ]))->save();
    }

    protected function assertRegisteredEntity(string $entityType): ImportableEntityInterface
    {
        return $this->registry->resolve($entityType);
    }

    protected function assertOrganizationOwned(ImportSession $session): void
    {
        if (! $session->organization_id) {
            throw new InvalidArgumentException('Import session is missing organization ownership.');
        }
    }

    protected function assertSupportedUpload(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, SpreadsheetReader::SUPPORTED_EXTENSIONS, true)) {
            throw new InvalidArgumentException(
                "Unsupported import file type [{$extension}]. Supported: csv, xlsx."
            );
        }
    }

    protected function absolutePath(ImportSession $session): string
    {
        $path = Storage::disk($session->disk)->path($session->stored_path);

        if (! is_file($path)) {
            throw new RuntimeException('Import session file is missing from storage.');
        }

        return $path;
    }

    /**
     * Prefer an explicit session mapping when any field is mapped; otherwise use detection.
     *
     * @param  array<string, string|null>|null  $sessionMapping
     * @param  array<string, string|null>  $detectedMapping
     * @return array<string, string|null>
     */
    protected function resolveColumnMapping(?array $sessionMapping, array $detectedMapping): array
    {
        if (! is_array($sessionMapping) || $sessionMapping === []) {
            return $detectedMapping;
        }

        $hasMappedField = collect($sessionMapping)->contains(
            static fn ($header) => is_string($header) && trim($header) !== ''
        );

        return $hasMappedField ? $sessionMapping : $detectedMapping;
    }

    /**
     * @param  list<string>  $headers
     * @param  array<string, string|null>  $mapping
     * @return list<string>
     */
    protected function unknownColumnsForMapping(array $headers, array $mapping): array
    {
        $mappedHeaders = collect($mapping)
            ->filter(static fn ($header) => is_string($header) && trim($header) !== '')
            ->values()
            ->all();

        return array_values(array_filter(
            $headers,
            static fn (string $header): bool => ! in_array($header, $mappedHeaders, true)
        ));
    }

    /**
     * @param  list<ImportFieldDefinition>  $fields
     * @param  array<string, string|null>  $mapping
     * @return list<string>
     */
    protected function unmappedRequiredFields(array $fields, array $mapping): array
    {
        $unmapped = [];

        foreach ($fields as $field) {
            if (! $field->required) {
                continue;
            }

            $header = $mapping[$field->key] ?? null;
            if (! is_string($header) || trim($header) === '') {
                $unmapped[] = $field->key;
            }
        }

        return $unmapped;
    }
}
