<?php

namespace App\Services\Recruitment;

use App\Events\ResumeUploaded;
use App\Models\Candidate;
use App\Models\CandidateResume;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ResumeService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function upload(Candidate $candidate, string $name, UploadedFile $file, bool $setDefault = false): CandidateResume
    {
        $this->validateResume($file);

        return DB::transaction(function () use ($candidate, $name, $file, $setDefault): CandidateResume {
            $disk = config('hrms.recruitment.documents.disk', 'local');
            $path = $file->store(
                sprintf('candidate-resumes/%d/%d', $candidate->organization_id, $candidate->id),
                $disk,
            );

            $resume = CandidateResume::query()->create([
                'organization_id' => $candidate->organization_id,
                'candidate_id' => $candidate->id,
                'name' => $name,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'is_default' => false,
                'uploaded_at' => now(),
            ]);

            if ($setDefault || ! CandidateResume::query()->where('candidate_id', $candidate->id)->where('is_default', true)->exists()) {
                $this->setDefault($resume);
            }

            $this->auditLogger->log($resume, 'candidate_resume_uploaded', [
                'candidate_id' => $candidate->id,
                'name' => $resume->name,
                'file' => $resume->original_name,
            ]);

            event(ResumeUploaded::forModel($resume));

            return $resume->fresh();
        });
    }

    public function setDefault(CandidateResume $resume): CandidateResume
    {
        return DB::transaction(function () use ($resume): CandidateResume {
            CandidateResume::query()
                ->where('candidate_id', $resume->candidate_id)
                ->whereKeyNot($resume->id)
                ->update(['is_default' => false]);

            $resume->update(['is_default' => true]);
            $resume->candidate?->update(['resume_path' => $resume->path]);

            return $resume->fresh();
        });
    }

    public function delete(CandidateResume $resume): void
    {
        DB::transaction(function () use ($resume): void {
            $wasDefault = $resume->is_default;
            $candidate = $resume->candidate;

            Storage::disk($resume->disk)->delete($resume->path);
            $this->auditLogger->log($resume, 'candidate_resume_deleted', [
                'candidate_id' => $resume->candidate_id,
                'name' => $resume->name,
            ]);
            $resume->delete();

            if ($wasDefault && $candidate) {
                $next = CandidateResume::query()
                    ->where('candidate_id', $candidate->id)
                    ->latest('uploaded_at')
                    ->first();

                if ($next) {
                    $this->setDefault($next);
                } else {
                    $candidate->update(['resume_path' => null]);
                }
            }
        });
    }

    protected function validateResume(UploadedFile $file): void
    {
        $allowed = config('hrms.recruitment.portal.resume_mime_types', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);

        $maxKb = config('hrms.recruitment.portal.resume_max_kb', 5120);

        if (! in_array($file->getMimeType(), $allowed, true)) {
            throw ValidationException::withMessages([
                'resume' => 'Invalid resume file type. Allowed: PDF, DOC, DOCX.',
            ]);
        }

        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'resume' => 'Resume file is too large.',
            ]);
        }
    }
}
