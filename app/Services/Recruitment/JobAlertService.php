<?php

namespace App\Services\Recruitment;

use App\Models\CandidateAccount;
use App\Models\CandidateJobAlert;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobAlertService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function subscribe(CandidateAccount $account, array $data): CandidateJobAlert
    {
        if (
            empty($data['department_id'])
            && empty($data['skills'])
            && empty($data['location'])
            && empty($data['employment_type'])
        ) {
            throw ValidationException::withMessages([
                'alert' => 'At least one alert criterion is required.',
            ]);
        }

        return DB::transaction(function () use ($account, $data): CandidateJobAlert {
            $alert = CandidateJobAlert::query()->create([
                'organization_id' => $account->organization_id,
                'candidate_account_id' => $account->id,
                'department_id' => $data['department_id'] ?? null,
                'skills' => $data['skills'] ?? null,
                'location' => $data['location'] ?? null,
                'employment_type' => $data['employment_type'] ?? null,
                'is_active' => true,
            ]);

            $this->auditLogger->log($alert, 'candidate_job_alert_created', [
                'department_id' => $alert->department_id,
                'location' => $alert->location,
            ]);

            return $alert->load('department');
        });
    }

    public function update(CandidateJobAlert $alert, CandidateAccount $account, array $data): CandidateJobAlert
    {
        $this->assertOwnership($alert, $account);

        return DB::transaction(function () use ($alert, $data): CandidateJobAlert {
            $alert->update($data);
            $this->auditLogger->log($alert, 'candidate_job_alert_updated', [
                'is_active' => $alert->is_active,
            ]);

            return $alert->fresh('department');
        });
    }

    public function unsubscribe(CandidateJobAlert $alert, CandidateAccount $account): void
    {
        $this->assertOwnership($alert, $account);

        DB::transaction(function () use ($alert): void {
            $this->auditLogger->log($alert, 'candidate_job_alert_deleted', []);
            $alert->delete();
        });
    }

    public function listForAccount(CandidateAccount $account): \Illuminate\Support\Collection
    {
        return CandidateJobAlert::query()
            ->with('department')
            ->where('candidate_account_id', $account->id)
            ->latest()
            ->get();
    }

    protected function assertOwnership(CandidateJobAlert $alert, CandidateAccount $account): void
    {
        if ((int) $alert->candidate_account_id !== (int) $account->id) {
            abort(403);
        }
    }
}
