<?php

namespace App\Services\Hrms;

use App\Models\Employee;
use App\Models\EmployeeSkill;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeSkillService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function create(Employee $employee, array $data, User $actor): EmployeeSkill
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            $skill = $employee->skills()->create($this->normalize($data));

            $this->auditLogger->log($skill, 'employee_skill_created', [
                'employee_id' => $employee->id,
                'skill' => $skill->skill,
            ], $actor);

            return $skill;
        });
    }

    public function update(EmployeeSkill $skill, array $data, User $actor): EmployeeSkill
    {
        return DB::transaction(function () use ($skill, $data, $actor) {
            $skill->update($this->normalize($data));

            $this->auditLogger->log($skill, 'employee_skill_updated', [
                'employee_id' => $skill->employee_id,
                'skill' => $skill->skill,
            ], $actor);

            return $skill->fresh();
        });
    }

    public function delete(EmployeeSkill $skill, User $actor): void
    {
        DB::transaction(function () use ($skill, $actor) {
            $this->auditLogger->log($skill, 'employee_skill_deleted', [
                'employee_id' => $skill->employee_id,
                'skill' => $skill->skill,
            ], $actor);

            $skill->delete();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function syncForEmployee(Employee $employee, array $rows, User $actor): void
    {
        EmployeeSkill::query()->where('employee_id', $employee->id)->delete();

        foreach ($rows as $row) {
            if (blank($row['skill'] ?? null)) {
                continue;
            }
            $employee->skills()->create($this->normalize($row));
        }

        $this->auditLogger->log($employee, 'employee_skills_synced', [
            'count' => count($rows),
        ], $actor);
    }

    /** @param  array<string, mixed>  $data */
    protected function normalize(array $data): array
    {
        $proficiency = $data['proficiency'] ?? 'intermediate';
        if (! array_key_exists($proficiency, config('hrms.skill_proficiencies', []))) {
            throw ValidationException::withMessages([
                'proficiency' => __('Invalid skill proficiency.'),
            ]);
        }

        return [
            'skill' => $data['skill'],
            'proficiency' => $proficiency,
            'years_of_experience' => $data['years_of_experience'] ?? null,
            'last_used' => $data['last_used'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
