<?php

namespace App\Services\Hrms;

use App\Models\AttendanceOvertimeEntry;
use App\Models\AttendanceOvertimeRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AttendanceOvertimeListingService
{
    /**
     * Paginate overtime entries with filters, search, and sorting.
     *
     * Organization scoping is applied via BelongsToOrganization / OrganizationScope.
     */
    public function paginateEntries(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $query = AttendanceOvertimeEntry::query()
            ->with([
                'employee:id,organization_id,first_name,last_name,employee_code,branch_id,department_id',
                'employee.branch:id,name',
                'employee.department:id,name',
                'rule:id,name,code,rule_type',
                'reviewer:id,name',
            ]);

        $this->applyEntryFilters($query, $request);
        $this->applyEntrySorting($query, $request);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Paginate overtime rules with optional type/active filters.
     */
    public function paginateRules(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $query = AttendanceOvertimeRule::query();

        if ($request->filled('rule_type')) {
            $query->where('rule_type', $request->string('rule_type')->toString());
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim().'%';
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', $search)
                    ->orWhere('code', 'like', $search);
            });
        }

        $sort = $request->string('sort', 'latest')->toString();
        match ($sort) {
            'name' => $query->orderBy('name'),
            'rule_type' => $query->orderBy('rule_type')->orderBy('name'),
            default => $query->latest('id'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    protected function applyEntryFilters(Builder $query, Request $request): void
    {
        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if (in_array($status, AttendanceOvertimeEntry::statuses(), true)) {
                $query->where('attendance_overtime_entries.status', $status);
            }
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($request->filled('rule_id')) {
            $query->where('attendance_overtime_rule_id', (int) $request->input('rule_id'));
        }

        if ($request->filled('rule_type')) {
            $query->where('rule_type', $request->string('rule_type')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('attendance_date', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('attendance_date', '<=', $request->string('date_to')->toString());
        }

        if ($request->filled('branch_id') || $request->filled('department_id')) {
            $query->whereHas('employee', function (Builder $employee) use ($request): void {
                if ($request->filled('branch_id')) {
                    $employee->where('branch_id', (int) $request->input('branch_id'));
                }
                if ($request->filled('department_id')) {
                    $employee->where('department_id', (int) $request->input('department_id'));
                }
            });
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim().'%';
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('review_notes', 'like', $search)
                    ->orWhereHas('employee', function (Builder $employee) use ($search): void {
                        $employee->where('first_name', 'like', $search)
                            ->orWhere('last_name', 'like', $search)
                            ->orWhere('employee_code', 'like', $search);
                    })
                    ->orWhereHas('rule', function (Builder $rule) use ($search): void {
                        $rule->where('name', 'like', $search)
                            ->orWhere('code', 'like', $search);
                    });
            });
        }
    }

    protected function applyEntrySorting(Builder $query, Request $request): void
    {
        $sort = $request->string('sort', 'attendance_date')->toString();
        $direction = strtolower($request->string('direction', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $allowed = [
            'attendance_date',
            'minutes',
            'status',
            'rule_type',
            'id',
            'created_at',
        ];

        if (! in_array($sort, $allowed, true)) {
            $sort = 'attendance_date';
        }

        $query->orderBy($sort, $direction);

        if ($sort !== 'id') {
            $query->orderByDesc('id');
        }
    }
}
