<?php

namespace App\Services\Hrms;

use App\Models\AttendanceCorrection;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeAssetAssignment;
use App\Models\EmployeeDocument;
use App\Models\EmployeeExitProcess;
use App\Models\LeaveApplication;
use App\Models\ProjectMember;
use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

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

        $events = $events
            ->merge($this->auditEvents($employee, $limit))
            ->merge($this->leaveEvents($employee))
            ->merge($this->attendanceCorrectionEvents($employee))
            ->merge($this->documentEvents($employee))
            ->merge($this->assetEvents($employee))
            ->merge($this->exitEvents($employee))
            ->merge($this->projectAssignmentEvents($employee))
            ->merge($this->taskAssignmentEvents($employee))
            ->merge($this->loginActivityEvents($employee));

        return $events
            ->sortByDesc(fn (array $event) => $event['date'].' '.($event['metadata']['sort'] ?? ''))
            ->values()
            ->take($limit);
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function auditEvents(Employee $employee, int $limit): Collection
    {
        $events = collect();

        $auditEvents = AuditLog::query()
            ->where('auditable_type', Employee::class)
            ->where('auditable_id', $employee->id)
            ->whereIn('event', [
                'employee_created',
                'employee_updated',
                'employee_profile_updated',
                'employee_designation_changed',
                'employee_department_changed',
                'employee_branch_changed',
                'employee_reporting_manager_changed',
                'employee_status_changed',
                'employee_exited',
                'employee_user_linked',
                'employee_user_unlinked',
                'employee_skills_synced',
                'employee_certifications_synced',
                'employee_educations_synced',
                'employee_experiences_synced',
            ])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        foreach ($auditEvents as $log) {
            $type = match ($log->event) {
                'employee_created' => 'created',
                'employee_designation_changed' => 'promotion',
                'employee_department_changed' => 'department_transfer',
                'employee_branch_changed' => 'branch_transfer',
                'employee_reporting_manager_changed' => 'manager_change',
                'employee_status_changed' => 'status_change',
                'employee_exited' => 'exit',
                'employee_profile_updated', 'employee_updated' => 'profile_update',
                'employee_skills_synced', 'employee_certifications_synced',
                'employee_educations_synced', 'employee_experiences_synced' => 'profile_update',
                'employee_user_linked', 'employee_user_unlinked' => 'login_account',
                default => 'employee_update',
            };

            $events->push([
                'type' => $type,
                'label' => $this->labelForAuditEvent($log->event),
                'date' => $log->created_at->toDateString(),
                'description' => $this->descriptionForAuditEvent($log),
                'metadata' => array_merge($log->properties ?? [], ['sort' => $log->created_at->toTimeString()]),
            ]);
        }

        return $events;
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function leaveEvents(Employee $employee): Collection
    {
        $events = collect();

        LeaveApplication::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'rejected', 'cancelled', 'pending'])
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

        return $events;
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function attendanceCorrectionEvents(Employee $employee): Collection
    {
        $events = collect();

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

        return $events;
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function documentEvents(Employee $employee): Collection
    {
        $events = collect();

        EmployeeDocument::query()
            ->where('employee_id', $employee->id)
            ->latest()
            ->limit(20)
            ->get()
            ->each(function (EmployeeDocument $document) use ($events): void {
                $events->push([
                    'type' => 'document_upload',
                    'label' => 'Document Uploaded',
                    'date' => $document->created_at->toDateString(),
                    'description' => ($document->title ?? 'Document').' uploaded',
                    'metadata' => ['category' => $document->category],
                ]);

                if ($document->verification_status === 'verified') {
                    $events->push([
                        'type' => 'document_verified',
                        'label' => 'Document Verified',
                        'date' => ($document->verified_at ?? $document->updated_at)->toDateString(),
                        'description' => ($document->title ?? 'Document').' verified',
                        'metadata' => ['category' => $document->category],
                    ]);
                }
            });

        return $events;
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function assetEvents(Employee $employee): Collection
    {
        $events = collect();

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

        return $events;
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function exitEvents(Employee $employee): Collection
    {
        $events = collect();

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

        return $events;
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function projectAssignmentEvents(Employee $employee): Collection
    {
        $events = collect();

        if (! $employee->user_id || ! Schema::hasTable('project_members')) {
            return $events;
        }

        ProjectMember::query()
            ->where('user_id', $employee->user_id)
            ->with('project:id,name,code')
            ->latest('joined_at')
            ->limit(15)
            ->get()
            ->each(function (ProjectMember $member) use ($events): void {
                if ($member->joined_at) {
                    $events->push([
                        'type' => 'project_assignment',
                        'label' => 'Project Assignment',
                        'date' => $member->joined_at->toDateString(),
                        'description' => 'Assigned to '.($member->project->name ?? 'project'),
                        'metadata' => ['project_id' => $member->project_id],
                    ]);
                }
            });

        return $events;
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function taskAssignmentEvents(Employee $employee): Collection
    {
        $events = collect();

        if (! $employee->user_id || ! Schema::hasTable('tasks')) {
            return $events;
        }

        Task::query()
            ->where('assigned_to', $employee->user_id)
            ->latest('updated_at')
            ->limit(15)
            ->get(['id', 'title', 'status', 'created_at', 'updated_at'])
            ->each(function (Task $task) use ($events): void {
                $events->push([
                    'type' => 'task_assignment',
                    'label' => 'Task Assignment',
                    'date' => ($task->created_at ?? $task->updated_at)->toDateString(),
                    'description' => ($task->title ?? 'Task').' ('.$task->status.')',
                    'metadata' => ['task_id' => $task->id, 'status' => $task->status],
                ]);
            });

        return $events;
    }

    /** @return Collection<int, array<string, mixed>> */
    protected function loginActivityEvents(Employee $employee): Collection
    {
        $events = collect();

        if (! $employee->user_id) {
            return $events;
        }

        $loginLogs = AuditLog::query()
            ->where('auditable_type', \App\Models\User::class)
            ->where('auditable_id', $employee->user_id)
            ->whereIn('event', ['user_logged_in', 'login', 'user_login'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        foreach ($loginLogs as $log) {
            $events->push([
                'type' => 'login_activity',
                'label' => 'Login Activity',
                'date' => $log->created_at->toDateString(),
                'description' => 'User login recorded',
                'metadata' => $log->properties ?? [],
            ]);
        }

        return $events;
    }

    protected function labelForAuditEvent(string $event): string
    {
        return match ($event) {
            'employee_created' => 'Employee Created',
            'employee_updated' => 'Profile Updated',
            'employee_profile_updated' => 'Profile Updated',
            'employee_designation_changed' => 'Designation Changed',
            'employee_department_changed' => 'Department Transfer',
            'employee_branch_changed' => 'Branch Transfer',
            'employee_reporting_manager_changed' => 'Manager Change',
            'employee_status_changed' => 'Status Changed',
            'employee_exited' => 'Employee Exited',
            'employee_user_linked' => 'Login Linked',
            'employee_user_unlinked' => 'Login Unlinked',
            'employee_skills_synced' => 'Skills Updated',
            'employee_certifications_synced' => 'Certifications Updated',
            'employee_educations_synced' => 'Education Updated',
            'employee_experiences_synced' => 'Experience Updated',
            default => 'Employee Update',
        };
    }

    /** @param AuditLog $log */
    protected function descriptionForAuditEvent($log): string
    {
        $properties = $log->properties ?? [];

        return match ($log->event) {
            'employee_created' => 'Employee record created',
            'employee_designation_changed' => 'Designation / promotion recorded',
            'employee_department_changed' => 'Department transfer recorded',
            'employee_branch_changed' => 'Branch transfer recorded',
            'employee_reporting_manager_changed' => 'Reporting manager changed',
            'employee_status_changed' => 'Status changed to '.($properties['to'] ?? 'unknown'),
            'employee_exited' => 'Employee exit recorded',
            'employee_profile_updated', 'employee_updated' => 'Employee profile updated',
            'employee_skills_synced' => 'Skills list updated',
            'employee_certifications_synced' => 'Certifications list updated',
            'employee_educations_synced' => 'Education history updated',
            'employee_experiences_synced' => 'Experience history updated',
            default => 'Employee record updated',
        };
    }
}
