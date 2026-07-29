<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\ProjectMemberService;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use App\Services\TimeTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskAssigneeCollaborationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: User, 2: Organization, 3: Project, 4: Task}
     */
    protected function setupAssignedEmployee(): array
    {
        $owner = User::factory()->create();
        $employee = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($owner, 'organization-owner');
        $organization->addMember($employee, 'employee');

        app(TenantContext::class)->set($organization);
        app(TaskDefaultsService::class)->seedAll($organization);

        $project = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Assignee Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $owner);

        app(ProjectMemberService::class)->add($project, $employee, 'team_member', $owner);

        $task = app(TaskService::class)->createWorkManagement([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Assigned Work',
            'assigned_to' => $employee->id,
        ], $owner);

        return [$owner, $employee, $organization, $project, $task];
    }

    public function test_assigned_employee_can_update_own_status_but_not_reassign(): void
    {
        [, $employee, $organization, , $task] = $this->setupAssignedEmployee();

        $this->actingAs($employee)->withSession(['current_organization_id' => $organization->id])
            ->put(route('tasks.update', $task), [
                'status' => 'in_progress',
                'completion_percentage' => 25,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
            'completion_percentage' => 25,
            'assigned_to' => $employee->id,
        ]);

        $outsider = User::factory()->create();
        $organization->addMember($outsider, 'employee');

        $this->actingAs($employee)->withSession(['current_organization_id' => $organization->id])
            ->put(route('tasks.update', $task), [
                'title' => 'Hacked Title',
                'assigned_to' => $outsider->id,
                'status' => 'completed',
            ]);

        $task->refresh();
        $this->assertSame('Assigned Work', $task->title);
        $this->assertSame($employee->id, (int) $task->assigned_to);
    }

    public function test_assigned_employee_can_manage_checklist(): void
    {
        [, $employee, $organization, , $task] = $this->setupAssignedEmployee();

        $this->actingAs($employee)->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.checklists.store', $task), [
                'title' => 'Write tests',
            ])
            ->assertRedirect();

        $item = TaskChecklist::query()->where('task_id', $task->id)->first();
        $this->assertNotNull($item);

        $this->actingAs($employee)->withSession(['current_organization_id' => $organization->id])
            ->patch(route('tasks.checklists.complete', [$task, $item]))
            ->assertRedirect();

        $this->assertTrue((bool) $item->fresh()->is_completed);
    }

    public function test_assigned_employee_can_comment_and_edit_own_comment(): void
    {
        [, $employee, $organization, , $task] = $this->setupAssignedEmployee();

        $this->actingAs($employee)->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.comments.store', $task), [
                'comment' => 'Working on it @nobody',
            ])
            ->assertRedirect();

        $comment = TaskComment::query()->where('task_id', $task->id)->first();
        $this->assertNotNull($comment);

        $this->actingAs($employee)->withSession(['current_organization_id' => $organization->id])
            ->patch(route('tasks.comments.update', [$task, $comment]), [
                'comment' => 'Updated progress note',
            ])
            ->assertRedirect();

        $this->assertSame('Updated progress note', $comment->fresh()->comment);
    }

    public function test_unassigned_employee_cannot_comment(): void
    {
        [$owner, , $organization, $project] = $this->setupAssignedEmployee();
        $other = User::factory()->create();
        $organization->addMember($other, 'employee');
        app(ProjectMemberService::class)->add($project, $other, 'team_member', $owner);

        $task = app(TaskService::class)->createWorkManagement([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Someone Else',
            'assigned_to' => $owner->id,
        ], $owner);

        $this->actingAs($other)->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.comments.store', $task), [
                'comment' => 'Should fail',
            ])
            ->assertForbidden();
    }

    public function test_assigned_employee_can_log_time_with_timer_controls(): void
    {
        [, $employee, $organization, , $task] = $this->setupAssignedEmployee();
        $tracking = app(TimeTrackingService::class);

        $this->actingAs($employee)->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.time-logs.start', $task))
            ->assertRedirect();

        $tracking->pauseTimer($task, $employee);
        $tracking->resumeTimer($task, $employee);
        $log = $tracking->stopTimer($task, $employee);

        $this->assertNotNull($log->end_time);
        $this->assertSame('timer', $log->source);
    }

    public function test_assigned_employee_can_upload_attachment_when_enabled(): void
    {
        Storage::fake('public');
        [, $employee, $organization, , $task] = $this->setupAssignedEmployee();

        $this->actingAs($employee)->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.attachments.store', $task), [
                'file' => UploadedFile::fake()->create('notes.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $task->id,
            'uploaded_by' => $employee->id,
            'file_name' => 'notes.pdf',
        ]);
    }

    public function test_attachments_blocked_when_feature_flag_disabled(): void
    {
        config(['attachments.task_attachments_enabled' => false]);
        [, $employee, $organization, , $task] = $this->setupAssignedEmployee();

        $this->actingAs($employee)->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.attachments.store', $task), [
                'file' => UploadedFile::fake()->create('notes.pdf', 120, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_assigned_employee_cannot_delete_task(): void
    {
        [, $employee, $organization, , $task] = $this->setupAssignedEmployee();

        $this->actingAs($employee)->withSession(['current_organization_id' => $organization->id])
            ->delete(route('tasks.destroy', $task))
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }
}
