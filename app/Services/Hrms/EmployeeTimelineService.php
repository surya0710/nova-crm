<?php

namespace App\Services\Hrms;

use App\Models\AttendanceCorrection;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeAssetAssignment;
use App\Models\EmployeeDocument;
use App\Models\EmployeeExitProcess;
use App\Models\LeaveApplication;
use Illuminate\Support\Collection;

class EmployeeTimelineService
{
    /** @return Collection<int, array<string, mixed>> */
    public function timelineForEmployee(Employee $employee, ?int $limit = 50): Collection
    {
        $events = collect();

        if ($employee->joining_date) {
            $events->push([
                'type' => 'joined',
                'label' => 'Joined',
                'date' => $employee->joining_date->toDateString(),
                'description' => 'Employee joined the organization',
                'metadata' => ['employee_code' => $employee->employee_code],
            ]);
        }

        $auditEvents = AuditLog::query()
            ->where('auditable_type', Employee::class)
            ->where('auditable_id', $employee->id)
            ->whereIn('event', [
                'employee_designation_changed',
                'employee_department_changed',
                'employee_reporting_manager_changed',
                'employee_status_changed',
                'employee_exited',
            ])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        foreach ($auditEvents as $log) {
            $type = match ($log->event) {
                'employee_designation_changed' => 'promotion',
                'employee_department_changed' => 'department_transfer',
                'employee_reporting_manager_changed' => 'manager_change',
                'employee_status_changed' => 'status_change',
                'employee_exited' => 'exit',
                default => 'employee_update',
            };

            $events->push([
                'type' => $type,
                'label' => $this->labelForAuditEvent($log->event),
                'date' => $log->created_at->toDateString(),
                'description' => $this->descriptionForAuditEvent($log),
                'metadata' => $log->properties ?? [],
            ]);
        }

        LeaveApplication::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'rejected', 'cancelled'])
            ->with('leaveType')
            ->latest('submitted_at')
            ->limit(20)
            ->get()
            ->each(function (LeaveApplication $leave) use ($events): void {
                $events->push([
                    'type' => 'leave',
                    'label' => 'Leave '.ucfirst($leave->status),
                    'date' => ($leave->submitted_at ?? $leave->created_at)->toDateString(),
                    'description' => ($leave->leaveType->name ?? 'Leave').': '.$leave->start_date->format('M j').' – '.$leave->end_date->format('M j'),
                    'metadata' => ['status' => $leave->status, 'days' => $leave->days],
                ]);
            });

        AttendanceCorrection::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->latest()
            ->limit(20)
            ->get()
            ->each(function (AttendanceCorrection $correction) use ($events): void {
                $events->push([
                    'type' => 'attendance_correction',
                    'label' => 'Attendance Correction '.ucfirst($correction->status),
                    'date' => $correction->created_at->toDateString(),
                    'description' => 'Attendance correction '.$correction->status,
                    'metadata' => ['status' => $correction->status],
                ]);
            });

        EmployeeDocument::query()
            ->where('employee_id', $employee->id)
            ->where('verification_status', 'verified')
            ->latest('verified_at')
            ->limit(20)
            ->get()
            ->each(function (EmployeeDocument $document) use ($events): void {
                $events->push([
                    'type' => 'document_verified',
                    'label' => 'Document Verified',
                    'date' => ($document->verified_at ?? $document->updated_at)->toDateString(),
                    'description' => $document->title.' verified',
                    'metadata' => ['category' => $document->category],
                ]);
            });

        EmployeeAssetAssignment::query()
            ->where('employee_id', $employee->id)
            ->with('asset')
            ->latest('assigned_date')
            ->limit(20)
            ->get()
            ->each(function (EmployeeAssetAssignment $assignment) use ($events): void {
                $events->push([
                    'type' => 'asset_assigned',
                    'label' => 'Asset Assigned',
                    'date' => $assignment->assigned_date->toDateString(),
                    'description' => ($assignment->asset->name ?? 'Asset').' assigned',
                    'metadata' => [
                        'asset_code' => $assignment->asset->asset_code ?? null,
                        'returned' => $assignment->return_date !== null,
                    ],
                ]);
            });

        EmployeeExitProcess::query()
            ->where('employee_id', $employee->id)
            ->latest()
            ->limit(10)
            ->get()
            ->each(function (EmployeeExitProcess $exit) use ($events): void {
                $events->push([
                    'type' => 'exit_process',
                    'label' => 'Exit Process '.ucfirst(str_replace('_', ' ', $exit->status)),
                    'date' => $exit->created_at->toDateString(),
                    'description' => ucfirst(str_replace('_', ' ', $exit->exit_type)).' — '.$exit->status,
                    'metadata' => ['exit_type' => $exit->exit_type, 'status' => $exit->status],
                ]);
            });

        return $events
            ->sortByDesc(fn (array $event) => $event['date'])
            ->values()
            ->take($limit);
    }

    protected function labelForAuditEvent(string $event): string
    {
        return match ($event) {
            'employee_designation_changed' => 'Designation Changed',
            'employee_department_changed' => 'Department Transfer',
            'employee_reporting_manager_changed' => 'Manager Change',
            'employee_status_changed' => 'Status Changed',
            'employee_exited' => 'Employee Exited',
            default => 'Employee Update',
        };
    }

    /** @param AuditLog $log */
    protected function descriptionForAuditEvent($log): string
    {
        $properties = $log->properties ?? [];

        return match ($log->event) {
            'employee_designation_changed' => 'Designation changed',
            'employee_department_changed' => 'Department transfer recorded',
            'employee_reporting_manager_changed' => 'Reporting manager changed',
            'employee_status_changed' => 'Status changed to '.($properties['to'] ?? 'unknown'),
            'employee_exited' => 'Employee exit recorded',
            default => 'Employee record updated',
        };
    }
}
