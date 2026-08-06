<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\EmployeeExperience;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class EmployeeExperienceService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function create(Employee $employee, array $data, User $actor): EmployeeExperience
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            $experience = $employee->experiences()->create($this->normalize($data));

            $this->auditLogger->log($experience, 'employee_experience_created', [
                'employee_id' => $employee->id,
            ], $actor);

            return $experience;
        });
    }

    public function update(EmployeeExperience $experience, array $data, User $actor): EmployeeExperience
    {
        return DB::transaction(function () use ($experience, $data, $actor) {
            $experience->update($this->normalize($data));

            $this->auditLogger->log($experience, 'employee_experience_updated', [
                'employee_id' => $experience->employee_id,
            ], $actor);

            return $experience->fresh();
        });
    }

    public function delete(EmployeeExperience $experience, User $actor): void
    {
        DB::transaction(function () use ($experience, $actor) {
            $this->auditLogger->log($experience, 'employee_experience_deleted', [
                'employee_id' => $experience->employee_id,
            ], $actor);

            $experience->delete();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function syncForEmployee(Employee $employee, array $rows, User $actor): void
    {
        EmployeeExperience::query()->where('employee_id', $employee->id)->delete();

        foreach ($rows as $row) {
            if (blank($row['company'] ?? null)) {
                continue;
            }
            $employee->experiences()->create($this->normalize($row));
        }

        $this->auditLogger->log($employee, 'employee_experiences_synced', [
            'count' => count($rows),
        ], $actor);
    }

    /** @param  array<string, mixed>  $data */
    protected function normalize(array $data): array
    {
        $technologies = $data['technologies'] ?? null;
        if (is_array($technologies)) {
            $technologies = implode(', ', array_filter(array_map('trim', $technologies)));
        }

        return [
            'company' => $data['company'],
            'title' => $data['title'] ?? $data['designation'] ?? null,
            'employment_type' => $data['employment_type'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'technologies' => $technologies,
            'description' => $data['description'] ?? $data['responsibilities'] ?? null,
        ];
    }
}
