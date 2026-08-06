<?php

namespace App\Services\Recruitment;

use App\Events\CandidateProfileUpdated;
use App\Models\Candidate;
use App\Models\CandidateAccount;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CandidateProfileService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function updateProfile(CandidateAccount $account, array $data, ?UploadedFile $photo = null): Candidate
    {
        return DB::transaction(function () use ($account, $data, $photo): Candidate {
            $candidate = $account->candidate;
            $before = $candidate->only([
                'first_name', 'last_name', 'phone', 'address', 'city', 'state', 'country',
                'postal_code', 'current_company', 'current_designation', 'experience',
                'notice_period', 'expected_salary', 'skills', 'linkedin', 'github', 'portfolio',
                'education', 'work_experience', 'languages', 'certifications',
                'availability_date', 'preferred_locations',
            ]);

            if ($photo !== null) {
                $disk = config('hrms.recruitment.documents.disk', 'local');
                if ($candidate->profile_photo_path) {
                    Storage::disk($disk)->delete($candidate->profile_photo_path);
                }
                $data['profile_photo_path'] = $photo->store(
                    sprintf('candidate-profiles/%d', $candidate->organization_id),
                    $disk,
                );
            }

            $candidate->update($data);
            $candidate->refresh();

            $this->auditLogger->log($candidate, 'candidate_profile_updated', [
                'candidate_account_id' => $account->id,
                'before' => $before,
                'after' => $candidate->only(array_keys($before)),
            ]);

            event(CandidateProfileUpdated::forModel($candidate, ['candidate_account_id' => $account->id]));

            return $candidate;
        });
    }

    public function profileCompletion(Candidate $candidate): int
    {
        $fields = [
            'first_name', 'last_name', 'phone', 'address', 'experience',
            'skills', 'resume_path', 'linkedin', 'education', 'work_experience',
        ];

        $filled = collect($fields)->filter(function (string $field) use ($candidate): bool {
            $value = $candidate->{$field};

            if (is_array($value)) {
                return ! empty($value);
            }

            return filled($value);
        })->count();

        return (int) round(($filled / count($fields)) * 100);
    }

    public function buildProfileSnapshot(Candidate $candidate): array
    {
        return $candidate->only([
            'first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state',
            'country', 'postal_code', 'current_company', 'current_designation',
            'experience', 'notice_period', 'expected_salary', 'skills', 'education',
            'work_experience', 'languages', 'certifications', 'linkedin', 'github',
            'portfolio', 'availability_date', 'preferred_locations', 'resume_path',
        ]);
    }
}
