<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\EmployeeCertification;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeCertificationService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function create(Employee $employee, array $data, User $actor): EmployeeCertification
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            $certification = $employee->certifications()->create($this->normalize($data));

            $this->auditLogger->log($certification, 'employee_certification_created', [
                'employee_id' => $employee->id,
                'name' => $certification->name,
            ], $actor);

            return $certification;
        });
    }

    public function update(EmployeeCertification $certification, array $data, User $actor): EmployeeCertification
    {
        return DB::transaction(function () use ($certification, $data, $actor) {
            $certification->update($this->normalize($data));

            $this->auditLogger->log($certification, 'employee_certification_updated', [
                'employee_id' => $certification->employee_id,
                'name' => $certification->name,
            ], $actor);

            return $certification->fresh();
        });
    }

    public function delete(EmployeeCertification $certification, User $actor): void
    {
        DB::transaction(function () use ($certification, $actor) {
            $this->auditLogger->log($certification, 'employee_certification_deleted', [
                'employee_id' => $certification->employee_id,
                'name' => $certification->name,
            ], $actor);

            $certification->delete();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function syncForEmployee(Employee $employee, array $rows, User $actor): void
    {
        EmployeeCertification::query()->where('employee_id', $employee->id)->delete();

        foreach ($rows as $row) {
            if (blank($row['name'] ?? null)) {
                continue;
            }
            $employee->certifications()->create($this->normalize($row));
        }

        $this->auditLogger->log($employee, 'employee_certifications_synced', [
            'count' => count($rows),
        ], $actor);
    }

    /** @param  array<string, mixed>  $data */
    protected function normalize(array $data): array
    {
        $status = $data['status'] ?? 'active';
        if (! array_key_exists($status, config('hrms.certification_statuses', []))) {
            throw ValidationException::withMessages([
                'status' => __('Invalid certification status.'),
            ]);
        }

        $payload = [
            'name' => $data['name'],
            'issuing_organization' => $data['issuing_organization'] ?? null,
            'credential_number' => $data['credential_number'] ?? null,
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'credential_url' => $data['credential_url'] ?? null,
            'status' => $status,
        ];

        // Keep stored status aligned with expiry when dates imply otherwise.
        if (! empty($payload['expiry_date'])) {
            $temp = new EmployeeCertification($payload);
            $display = $temp->resolveDisplayStatus();
            if ($display === 'expired' && $status === 'active') {
                $payload['status'] = 'expired';
            }
        }

        return $payload;
    }
}
