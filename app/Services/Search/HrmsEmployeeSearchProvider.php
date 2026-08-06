<?php

namespace App\Services\Search;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

class HrmsEmployeeSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'employees';
    }

    public function label(): string
    {
        return __('Employees');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasAnyPermission(['hrms.view', 'employee.directory'])) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return Employee::query()
            ->with(['department', 'designation'])
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('employee_code', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('mobile', 'like', "%{$query}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$query}%"]);
            })
            ->limit($limit)
            ->get()
            ->map(fn (Employee $employee) => [
                'type' => __('Employee'),
                'label' => $this->label(),
                'title' => $employee->full_name,
                'subtitle' => trim(($employee->employee_code ?? '').' · '.($employee->department?->name ?? '')),
                'url' => route('hrms.employees.show', $employee),
                'workspace' => 'hr',
            ]);
    }
}
