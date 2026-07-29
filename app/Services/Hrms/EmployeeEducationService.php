<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class EmployeeEducationService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function create(Employee $employee, array $data, User $actor): EmployeeEducation
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            $education = $employee->educations()->create($this->normalize($data));

            $this->auditLogger->log($education, 'employee_education_created', [
                'employee_id' => $employee->id,
            ], $actor);

            return $education;
        });
    }

    public function update(EmployeeEducation $education, array $data, User $actor): EmployeeEducation
    {
        return DB::transaction(function () use ($education, $data, $actor) {
            $education->update($this->normalize($data));

            $this->auditLogger->log($education, 'employee_education_updated', [
                'employee_id' => $education->employee_id,
            ], $actor);

            return $education->fresh();
        });
    }

    public function delete(EmployeeEducation $education, User $actor): void
    {
        DB::transaction(function () use ($education, $actor) {
            $this->auditLogger->log($education, 'employee_education_deleted', [
                'employee_id' => $education->employee_id,
            ], $actor);

            $education->delete();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function syncForEmployee(Employee $employee, array $rows, User $actor): void
    {
        EmployeeEducation::query()->where('employee_id', $employee->id)->delete();

        foreach ($rows as $row) {
            if (blank($row['institution'] ?? null) && blank($row['degree'] ?? null)) {
                continue;
            }
            $employee->educations()->create($this->normalize($row));
        }

        $this->auditLogger->log($employee, 'employee_educations_synced', [
            'count' => count($rows),
        ], $actor);
    }

    /** @param  array<string, mixed>  $data */
    protected function normalize(array $data): array
    {
        $fieldOfStudy = $data['field_of_study'] ?? $data['specialization'] ?? null;
        $startDate = $data['start_date'] ?? null;
        $endDate = $data['end_date'] ?? null;

        return [
            'institution' => $data['institution'] ?? null,
            'degree' => $data['degree'] ?? null,
            'field_of_study' => $fieldOfStudy,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_year' => $data['start_year'] ?? ($startDate ? (int) date('Y', strtotime((string) $startDate)) : null),
            'end_year' => $data['end_year'] ?? ($endDate ? (int) date('Y', strtotime((string) $endDate)) : null),
            'grade' => $data['grade'] ?? null,
            'description' => $data['description'] ?? null,
        ];
    }
}
