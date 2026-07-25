<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class LeaveBalanceWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'leave_balance';
    }

    public function subscriptionModule(): ?string
    {
        return 'hrms';
    }

    public function permissionSlug(): ?string
    {
        return 'leave.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        if (! Schema::hasTable('leave_balances')) {
            return ['balances' => [], 'available' => false];
        }

        $employee = \App\Models\Employee::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $employee) {
            return ['balances' => [], 'available' => false];
        }

        $balances = \App\Models\LeaveBalance::query()
            ->with('leaveType:id,name')
            ->where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->get()
            ->map(fn ($b) => [
                'leave_type' => $b->leaveType?->name,
                'available' => $b->balance,
            ]);

        return ['balances' => $balances, 'available' => true];
    }
}
