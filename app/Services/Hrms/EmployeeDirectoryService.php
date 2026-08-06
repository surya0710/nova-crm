<?php

namespace App\Services\Hrms;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\HrmsTeam;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EmployeeDirectoryService
{
    /** @return LengthAwarePaginator<int, Employee> */
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Employee::query()
            ->with(['department', 'designation', 'branch', 'reportingManager'])
            ->whereIn('status', config('hrms.directory_visible_statuses', ['active', 'probation', 'notice_period']));

        if ($search = trim($filters['q'] ?? '')) {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($departmentId = $filters['department_id'] ?? null) {
            $query->where('department_id', $departmentId);
        }

        if ($designationId = $filters['designation_id'] ?? null) {
            $query->where('designation_id', $designationId);
        }

        if ($branchId = $filters['branch_id'] ?? null) {
            $query->where('branch_id', $branchId);
        }

        if ($teamId = $filters['team_id'] ?? null) {
            $team = HrmsTeam::query()->find($teamId);
            if ($team) {
                $query->where(function (Builder $inner) use ($team): void {
                    $inner->where('department_id', $team->department_id)
                        ->orWhere('id', $team->team_lead_employee_id);
                });
            }
        }

        return $query->orderBy('first_name')->paginate($perPage);
    }

    /** @return array<string, mixed> */
    public function profileCard(Employee $employee): array
    {
        $employee->load(['department', 'designation', 'branch', 'reportingManager']);

        return [
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'full_name' => $employee->full_name,
            'photo' => $employee->profile_photo_path,
            'designation' => $employee->designation?->name,
            'department' => $employee->department?->name,
            'branch' => $employee->branch?->name,
            'manager' => $employee->reportingManager?->full_name,
            'email' => $employee->email,
            'phone' => $employee->phone ?? $employee->mobile,
            'status' => $employee->status,
        ];
    }

    /** @return array<string, mixed> */
    public function filterOptions(): array
    {
        return [
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'designations' => Designation::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'teams' => HrmsTeam::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }
}
