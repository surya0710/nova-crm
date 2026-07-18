<?php

namespace App\Services\Import;

use App\Contracts\Import\ImportableEntityInterface;
use App\Models\ImportSession;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
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

        $directory = 'imports/'.$organization->id.'/'.$entityType;
        $path = $file->store($directory, 'local');

        if ($path === false) {
            throw new RuntimeException('Unable to store import upload.');
        }

        return DB::transaction(function () use ($organization, $entityType, $file, $user, $path, $worksheetName) {
            $session = ImportSession::query()->create([
                'organization_id' => $organization->id,
                'entity_type' => $entityType,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'disk' => 'local',
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
            $result = $this->validator->validate(
                $parsed,
                $entity->fieldDefinitions(),
                $detection['mapping'],
                $detection['unknown_columns'],
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
                'column_mapping' => $detection['mapping'],
                'total_rows' => $result['total_rows'],
                'failed_count' => $result['invalid_rows'],
                'validation_summary' => [
                    'valid_rows' => $result['valid_rows'],
                    'invalid_rows' => $result['invalid_rows'],
                    'duplicate_rows' => $duplicateRows,
                    'unknown_columns' => $result['unknown_columns'],
                    'duplicate_columns' => $result['duplicate_columns'],
                    'unmapped_required' => $detection['unmapped_required'],
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

        $errors = $session->validation_summary['errors'] ?? null;

        if (! is_array($errors)) {
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

        $errors = $session->validation_summary['errors'] ?? null;

        if (! is_array($errors)) {
            $preview = $this->preview($session);
            $errors = $preview->errors;
        }

        return $this->errorReportGenerator->toCsvString($errors);
    }

    /**
     * Execute import for a ready session by invoking the entity adapter persistRow callback.
     */
    public function executeImport(ImportSession $session, ?User $user = null): ImportSession
    {
        $this->assertOrganizationOwned($session);
        $entity = $this->assertRegisteredEntity($session->entity_type);

        if ($session->status !== ImportSession::STATUS_READY) {
            throw new InvalidArgumentException(
                "Import session must be in ready status to execute, got [{$session->status}]."
            );
        }

        $this->transition($session, ImportSession::STATUS_IMPORTING);

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

            foreach ($result['preview_rows'] as $row) {
                $processed++;

                if (! $row['valid']) {
                    $isDuplicate = collect($row['errors'])->contains(
                        fn (string $message) => str_contains(strtolower($message), 'duplicate')
                    );

                    if ($isDuplicate) {
                        $skipped++;
                    } else {
                        $failed++;
                    }

                    foreach ($row['errors'] as $message) {
                        $rowErrors[] = [
                            'row_number' => $row['row_number'],
                            'column' => null,
                            'field' => null,
                            'error' => $message,
                            'value' => null,
                        ];
                    }
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
                } catch (\Throwable $e) {
                    $failed++;
                    $rowErrors[] = [
                        'row_number' => $row['row_number'],
                        'column' => null,
                        'field' => null,
                        'error' => $e->getMessage(),
                        'value' => null,
                    ];
                }
            }

            $summary = $session->validation_summary ?? [];
            $summary['execution_errors'] = $rowErrors;

            $this->transition($session, ImportSession::STATUS_COMPLETED, [
                'processed_rows' => $processed,
                'created_count' => $created,
                'updated_count' => $updated,
                'skipped_count' => $skipped,
                'failed_count' => $failed,
                'completed_at' => now(),
                'validation_summary' => $summary,
                'last_error' => null,
            ]);

            $this->auditLogger->log($session, 'import_completed', [
                'entity_type' => $session->entity_type,
                'processed_rows' => $processed,
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'failed' => $failed,
                'duplicate_rows' => $summary['duplicate_rows'] ?? 0,
            ], $user);

            return $session->fresh();
        } catch (\Throwable $e) {
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
            ], $user);

            throw $e;
        }
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
}
