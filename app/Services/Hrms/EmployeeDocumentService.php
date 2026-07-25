<?php

namespace App\Services\Hrms;

use App\Events\EmployeeDocumentDeleted;
use App\Events\EmployeeDocumentExpiring;
use App\Events\EmployeeDocumentUpdated;
use App\Events\EmployeeDocumentUploaded;
use App\Events\EmployeeDocumentVerified;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentVersion;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EmployeeDocumentService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    public function uploadDocument(Employee $employee, array $data, UploadedFile $file, User $actor): EmployeeDocument
    {
        return DB::transaction(function () use ($employee, $data, $file, $actor): EmployeeDocument {
            $document = EmployeeDocument::query()->create([
                'employee_id' => $employee->id,
                'category' => $data['category'],
                'title' => $data['title'],
                'expires_at' => $data['expires_at'] ?? null,
                'verification_status' => 'pending',
            ]);

            $version = $this->createVersion($document, $file, $data['notes'] ?? null, $actor);
            $document->update(['current_version_id' => $version->id]);
            $document->refresh();

            $this->auditLogger->log($document, 'employee_document_uploaded', [
                'category' => $document->category,
                'title' => $document->title,
                'version_no' => $version->version_no,
                'file' => $version->original_name,
            ], $actor);

            $this->auditLogger->log($document, 'employee_document_version_created', [
                'version_no' => $version->version_no,
                'file' => $version->original_name,
            ], $actor);

            event(EmployeeDocumentUploaded::forModel($document, ['actor_id' => $actor->id]));
            $this->maybeEmitExpiringEvent($document, $actor);

            return $document->load(['currentVersion.uploader', 'versions.uploader', 'verifier']);
        });
    }

    public function updateDocument(
        EmployeeDocument $document,
        array $data,
        ?UploadedFile $file,
        User $actor,
    ): EmployeeDocument {
        return DB::transaction(function () use ($document, $data, $file, $actor): EmployeeDocument {
            $before = $document->only(['category', 'title', 'expires_at']);

            $document->update([
                'category' => $data['category'] ?? $document->category,
                'title' => $data['title'] ?? $document->title,
                'expires_at' => array_key_exists('expires_at', $data) ? $data['expires_at'] : $document->expires_at,
            ]);

            if ($file !== null) {
                $version = $this->createVersion($document, $file, $data['notes'] ?? null, $actor);
                $document->update([
                    'current_version_id' => $version->id,
                    'verification_status' => 'pending',
                    'verified_by' => null,
                    'verified_at' => null,
                    'verification_notes' => null,
                ]);

                $this->auditLogger->log($document, 'employee_document_version_created', [
                    'version_no' => $version->version_no,
                    'file' => $version->original_name,
                ], $actor);
            }

            $document->refresh();

            $this->auditLogger->log($document, 'employee_document_updated', [
                'before' => $before,
                'after' => $document->only(array_keys($before)),
                'new_version' => $file !== null,
            ], $actor);

            event(EmployeeDocumentUpdated::forModel($document, ['actor_id' => $actor->id]));

            if (($before['expires_at']?->toDateString() ?? null) !== ($document->expires_at?->toDateString() ?? null)) {
                $this->maybeEmitExpiringEvent($document, $actor);
            }

            return $document->load(['currentVersion.uploader', 'versions.uploader', 'verifier']);
        });
    }

    public function verifyDocument(EmployeeDocument $document, array $data, User $actor): EmployeeDocument
    {
        return DB::transaction(function () use ($document, $data, $actor): EmployeeDocument {
            $before = $document->verification_status;

            $document->update([
                'verification_status' => $data['verification_status'],
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'verification_notes' => $data['verification_notes'] ?? null,
            ]);

            $document->refresh();

            $this->auditLogger->log($document, 'employee_document_verified', [
                'from' => $before,
                'to' => $document->verification_status,
                'notes' => $document->verification_notes,
            ], $actor);

            event(EmployeeDocumentVerified::forModel($document, ['actor_id' => $actor->id]));

            return $document->load(['currentVersion.uploader', 'versions.uploader', 'verifier']);
        });
    }

    public function deleteDocument(EmployeeDocument $document, User $actor): void
    {
        DB::transaction(function () use ($document, $actor): void {
            $this->auditLogger->log($document, 'employee_document_deleted', [
                'category' => $document->category,
                'title' => $document->title,
            ], $actor);

            event(EmployeeDocumentDeleted::forModel($document, ['actor_id' => $actor->id]));

            $document->delete();
        });
    }

    public function resolveDownloadVersion(EmployeeDocument $document, ?int $versionId = null): EmployeeDocumentVersion
    {
        if ($versionId !== null) {
            $version = $document->versions()->whereKey($versionId)->first();

            if ($version === null) {
                throw new NotFoundHttpException('Document version not found.');
            }

            return $version;
        }

        $version = $document->currentVersion;

        if ($version === null) {
            throw new NotFoundHttpException('Document has no file version.');
        }

        return $version;
    }

    public function logDownload(EmployeeDocument $document, EmployeeDocumentVersion $version, User $actor): void
    {
        $this->auditLogger->log($document, 'employee_document_downloaded', [
            'version_no' => $version->version_no,
            'file' => $version->original_name,
        ], $actor);
    }

    public function restoreVersion(EmployeeDocument $document, int $versionId, User $actor): EmployeeDocument
    {
        return DB::transaction(function () use ($document, $versionId, $actor): EmployeeDocument {
            $version = $document->versions()->whereKey($versionId)->firstOrFail();
            $previousVersionId = $document->current_version_id;

            $document->update(['current_version_id' => $version->id]);
            $document->refresh();

            $this->auditLogger->log($document, 'employee_document_version_restored', [
                'from_version_id' => $previousVersionId,
                'to_version_id' => $version->id,
                'version_no' => $version->version_no,
            ], $actor);

            event(EmployeeDocumentUpdated::forModel($document, [
                'actor_id' => $actor->id,
                'restored_version_no' => $version->version_no,
            ]));

            return $document->load(['currentVersion.uploader', 'versions.uploader', 'verifier']);
        });
    }

    protected function createVersion(
        EmployeeDocument $document,
        UploadedFile $file,
        ?string $notes,
        User $actor,
    ): EmployeeDocumentVersion {
        $fileMeta = $this->storeFile($document->employee, $file);
        $nextVersion = (int) $document->versions()->max('version_no') + 1;

        return EmployeeDocumentVersion::query()->create([
            'employee_document_id' => $document->id,
            'version_no' => $nextVersion,
            ...$fileMeta,
            'uploaded_by' => $actor->id,
            'notes' => $notes,
        ]);
    }

    /**
     * @return array{disk: string, path: string, original_name: string, mime_type: string|null, size: int}
     */
    protected function storeFile(Employee $employee, UploadedFile $file): array
    {
        $disk = $this->storageDisk();
        $directory = $this->storageDirectory($employee);
        $path = $file->store($directory, $disk);

        return [
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => (int) $file->getSize(),
        ];
    }

    protected function storageDisk(): string
    {
        return (string) config('hrms.documents.disk', 'local');
    }

    protected function storageDirectory(Employee $employee): string
    {
        return 'hrms-documents/'.$employee->organization_id.'/'.$employee->id;
    }

    protected function maybeEmitExpiringEvent(EmployeeDocument $document, User $actor): void
    {
        if (! $document->isExpiringSoon()) {
            return;
        }

        event(EmployeeDocumentExpiring::forModel($document, [
            'actor_id' => $actor->id,
            'expires_at' => $document->expires_at?->toDateString(),
        ]));
    }
}
