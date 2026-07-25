<?php

namespace App\Services\Recruitment;

use App\Models\CandidateAccount;
use App\Models\CandidateSavedJob;
use App\Models\JobOpening;
use App\Models\Organization;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavedJobService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function save(CandidateAccount $account, JobOpening $opening): CandidateSavedJob
    {
        if ((int) $opening->organization_id !== (int) $account->organization_id) {
            abort(403);
        }

        if ($opening->status !== 'published') {
            throw ValidationException::withMessages([
                'job_opening_id' => 'Only published jobs can be saved.',
            ]);
        }

        $existing = CandidateSavedJob::query()
            ->where('candidate_account_id', $account->id)
            ->where('job_opening_id', $opening->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($account, $opening): CandidateSavedJob {
            $saved = CandidateSavedJob::query()->create([
                'organization_id' => $account->organization_id,
                'candidate_account_id' => $account->id,
                'job_opening_id' => $opening->id,
            ]);

            $this->auditLogger->log($saved, 'candidate_job_saved', [
                'job_opening_id' => $opening->id,
            ]);

            return $saved->load('jobOpening');
        });
    }

    public function remove(CandidateAccount $account, JobOpening $opening): void
    {
        DB::transaction(function () use ($account, $opening): void {
            $saved = CandidateSavedJob::query()
                ->where('candidate_account_id', $account->id)
                ->where('job_opening_id', $opening->id)
                ->first();

            if ($saved) {
                $this->auditLogger->log($saved, 'candidate_job_unsaved', [
                    'job_opening_id' => $opening->id,
                ]);
                $saved->delete();
            }
        });
    }

    public function listForAccount(CandidateAccount $account): \Illuminate\Support\Collection
    {
        return CandidateSavedJob::query()
            ->with(['jobOpening.department', 'jobOpening.designation'])
            ->where('candidate_account_id', $account->id)
            ->latest()
            ->get();
    }

    public function isSaved(CandidateAccount $account, JobOpening $opening): bool
    {
        return CandidateSavedJob::query()
            ->where('candidate_account_id', $account->id)
            ->where('job_opening_id', $opening->id)
            ->exists();
    }
}
