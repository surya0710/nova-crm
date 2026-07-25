<?php

namespace App\Services\Hrms;

use App\Events\EmployeeExitCancelled;
use App\Events\EmployeeExitCompleted;
use App\Events\EmployeeExited;
use App\Events\EmployeeExitStarted;
use App\Models\Employee;
use App\Models\EmployeeExitProcess;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeExitService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {}

    public function start(Employee $employee, array $data, User $actor): EmployeeExitProcess
    {
        $existing = EmployeeExitProcess::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['in_progress', 'pending_approval'])
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages(['employee' => 'An active exit process already exists for this employee.']);
        }

        return DB::transaction(function () use ($employee, $data, $actor): EmployeeExitProcess {
            $exitProcess = EmployeeExitProcess::query()->create([
                ...$data,
                'employee_id' => $employee->id,
                'status' => 'in_progress',
                'initiated_by' => $actor->id,
            ]);

            $employee->update(['status' => 'notice_period']);

            $this->auditLogger->log($exitProcess, 'employee_exit_started', [
                'employee_id' => $employee->id,
                'exit_type' => $exitProcess->exit_type,
            ], $actor);
            event(EmployeeExitStarted::forModel($exitProcess, ['actor_id' => $actor->id]));

            return $exitProcess->load('employee');
        });
    }

    public function update(EmployeeExitProcess $exitProcess, array $data, User $actor): EmployeeExitProcess
    {
        if (in_array($exitProcess->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['exit_process' => 'This exit process is no longer editable.']);
        }

        return DB::transaction(function () use ($exitProcess, $data, $actor): EmployeeExitProcess {
            $before = $exitProcess->only([
                'exit_type', 'last_working_day', 'reason', 'exit_interview',
                'hr_notes', 'manager_notes', 'status',
                'checklist_assets_returned', 'checklist_documents_completed',
                'checklist_knowledge_transfer', 'checklist_manager_approval', 'checklist_hr_approval',
            ]);

            $exitProcess->update($data);

            $this->auditLogger->log($exitProcess, 'employee_exit_updated', [
                'before' => $before,
                'after' => $exitProcess->only(array_keys($before)),
            ], $actor);

            return $exitProcess->load('employee');
        });
    }

    public function complete(EmployeeExitProcess $exitProcess, User $actor): EmployeeExitProcess
    {
        if ($exitProcess->status !== 'in_progress' && $exitProcess->status !== 'pending_approval') {
            throw ValidationException::withMessages(['exit_process' => 'This exit process cannot be completed.']);
        }

        $checklist = [
            'checklist_assets_returned',
            'checklist_documents_completed',
            'checklist_knowledge_transfer',
            'checklist_manager_approval',
            'checklist_hr_approval',
        ];

        foreach ($checklist as $item) {
            if (! $exitProcess->{$item}) {
                throw ValidationException::withMessages(['exit_process' => 'All checklist items must be completed before finalizing exit.']);
            }
        }

        return DB::transaction(function () use ($exitProcess, $actor): EmployeeExitProcess {
            $exitProcess->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $statusMap = config('hrms.exit_type_status_map', []);
            $employeeStatus = $statusMap[$exitProcess->exit_type] ?? 'inactive';

            $exitProcess->employee->update([
                'status' => $employeeStatus,
                'exit_date' => $exitProcess->last_working_day,
            ]);

            $this->auditLogger->log($exitProcess, 'employee_exit_completed', [
                'employee_id' => $exitProcess->employee_id,
            ], $actor);
            event(EmployeeExitCompleted::forModel($exitProcess, ['actor_id' => $actor->id]));
            event(EmployeeExited::forModel($exitProcess->employee, ['actor_id' => $actor->id]));

            return $exitProcess->load('employee');
        });
    }

    public function cancel(EmployeeExitProcess $exitProcess, array $data, User $actor): EmployeeExitProcess
    {
        if (in_array($exitProcess->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['exit_process' => 'This exit process cannot be cancelled.']);
        }

        return DB::transaction(function () use ($exitProcess, $data, $actor): EmployeeExitProcess {
            $exitProcess->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'hr_notes' => $data['hr_notes'] ?? $exitProcess->hr_notes,
            ]);

            if ($exitProcess->employee->status === 'notice_period') {
                $exitProcess->employee->update(['status' => 'active']);
            }

            $this->auditLogger->log($exitProcess, 'employee_exit_cancelled', [
                'employee_id' => $exitProcess->employee_id,
            ], $actor);
            event(EmployeeExitCancelled::forModel($exitProcess, ['actor_id' => $actor->id]));

            return $exitProcess->load('employee');
        });
    }

    /** @return array<string, int> */
    public function dashboardStats(): array
    {
        return [
            'active' => EmployeeExitProcess::query()
                ->whereIn('status', ['in_progress', 'pending_approval'])
                ->count(),
            'completed_this_month' => EmployeeExitProcess::query()
                ->where('status', 'completed')
                ->whereMonth('completed_at', now()->month)
                ->whereYear('completed_at', now()->year)
                ->count(),
        ];
    }
}
