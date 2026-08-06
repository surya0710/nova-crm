<?php

namespace App\Services\Recruitment;

use App\Events\CandidateCreated;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CandidateService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    public function createCandidate(array $data, User $actor, ?UploadedFile $resume = null): Candidate
    {
        $this->assertUniqueEmail($data['email'], (int) $data['organization_id']);

        return DB::transaction(function () use ($data, $actor, $resume): Candidate {
            $candidate = Candidate::query()->create(array_merge($data, [
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]));

            if ($resume !== null) {
                $this->storeDocument($candidate, [
                    'category' => 'resume',
                    'title' => 'Resume',
                ], $resume, $actor);
            }

            $this->auditLogger->log($candidate, 'candidate_created', [
                'email' => $candidate->email,
                'name' => $candidate->fullName(),
            ], $actor);

            event(CandidateCreated::forModel($candidate, ['actor_id' => $actor->id]));

            return $candidate->load('documents');
        });
    }

    public function updateCandidate(Candidate $candidate, array $data, User $actor, ?UploadedFile $resume = null): Candidate
    {
        if (isset($data['email']) && strtolower($data['email']) !== strtolower($candidate->email)) {
            $this->assertUniqueEmail($data['email'], (int) $candidate->organization_id, $candidate->id);
        }

        return DB::transaction(function () use ($candidate, $data, $actor, $resume): Candidate {
            $before = $candidate->only([
                'first_name', 'last_name', 'email', 'phone', 'current_company',
                'current_designation', 'experience', 'expected_salary', 'source',
            ]);

            $candidate->update(array_merge($data, ['updated_by' => $actor->id]));
            $candidate->refresh();

            if ($resume !== null) {
                $this->storeDocument($candidate, [
                    'category' => 'resume',
                    'title' => 'Resume',
                ], $resume, $actor);
            }

            $this->auditLogger->log($candidate, 'candidate_updated', [
                'before' => $before,
                'after' => $candidate->only(array_keys($before)),
            ], $actor);

            return $candidate->load('documents');
        });
    }

    public function deleteCandidate(Candidate $candidate, User $actor): void
    {
        DB::transaction(function () use ($candidate, $actor): void {
            $this->auditLogger->log($candidate, 'candidate_deleted', [
                'email' => $candidate->email,
            ], $actor);
            $candidate->delete();
        });
    }

    public function uploadDocument(Candidate $candidate, array $data, UploadedFile $file, User $actor): CandidateDocument
    {
        return DB::transaction(function () use ($candidate, $data, $file, $actor): CandidateDocument {
            $document = $this->storeDocument($candidate, $data, $file, $actor);

            $this->auditLogger->log($document, 'candidate_document_uploaded', [
                'candidate_id' => $candidate->id,
                'category' => $document->category,
                'title' => $document->title,
                'file' => $document->original_name,
            ], $actor);

            return $document;
        });
    }

    public function deleteDocument(CandidateDocument $document, User $actor): void
    {
        DB::transaction(function () use ($document, $actor): void {
            $this->auditLogger->log($document, 'candidate_document_deleted', [
                'candidate_id' => $document->candidate_id,
                'title' => $document->title,
            ], $actor);
            $document->delete();
        });
    }

    protected function storeDocument(Candidate $candidate, array $data, UploadedFile $file, User $actor): CandidateDocument
    {
        $disk = config('hrms.recruitment.documents.disk', 'local');
        $directory = sprintf('recruitment-documents/%d/%d', $candidate->organization_id, $candidate->id);
        $path = $file->store($directory, $disk);

        $document = CandidateDocument::query()->create([
            'organization_id' => $candidate->organization_id,
            'candidate_id' => $candidate->id,
            'category' => $data['category'],
            'title' => $data['title'],
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $actor->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        if ($data['category'] === 'resume') {
            $candidate->update(['resume_path' => $path, 'updated_by' => $actor->id]);
        }

        return $document;
    }

    protected function assertUniqueEmail(string $email, int $organizationId, ?int $ignoreId = null): void
    {
        $query = Candidate::query()
            ->where('organization_id', $organizationId)
            ->whereRaw('LOWER(email) = ?', [strtolower($email)]);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'email' => 'A candidate with this email already exists in the organization.',
            ]);
        }
    }
}
