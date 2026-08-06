<?php

namespace App\Services\Search;

use App\Models\LeaveApplication;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

class HrmsLeaveSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'leave_requests';
    }

    public function label(): string
    {
        return __('Leave Requests');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('leave.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return LeaveApplication::query()
            ->with(['employee', 'leaveType'])
            ->where(function ($q) use ($query) {
                $q->where('status', 'like', "%{$query}%")
                    ->orWhere('reason', 'like', "%{$query}%")
                    ->orWhereHas('employee', function ($employee) use ($query) {
                        $employee->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhere('employee_code', 'like', "%{$query}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$query}%"]);
                    })
                    ->orWhereHas('leaveType', fn ($type) => $type->where('name', 'like', "%{$query}%"));
            })
            ->latest('submitted_at')
            ->limit($limit)
            ->get()
            ->map(fn (LeaveApplication $leave) => [
                'type' => __('Leave Request'),
                'label' => $this->label(),
                'title' => $leave->employee?->full_name ?? __('Leave request'),
                'subtitle' => trim(($leave->leaveType?->name ?? '').' · '.($leave->status ?? '')),
                'url' => route('hrms.leave-applications.show', $leave),
                'workspace' => 'hr',
            ]);
    }
}
