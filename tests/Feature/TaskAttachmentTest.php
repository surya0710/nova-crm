<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner, User $actor): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Attachment Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    protected function createTask(Organization $organization, Project $project, User $actor): Task
    {
        app(TenantContext::class)->set($organization);
        app(TaskDefaultsService::class)->seedAll($organization);

        return app(TaskService::class)->createWorkManagement([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Attachment Task',
        ], $actor);
    }

    public function test_user_can_upload_task_attachment(): void
    {
        Storage::fake('public');

        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createTask($organization, $project, $user);

        $file = UploadedFile::fake()->create('spec.pdf', 120, 'application/pdf');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.attachments.store', $task), [
                'file' => $file,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('task_attachments', [
            'organization_id' => $organization->id,
            'task_id' => $task->id,
            'file_name' => 'spec.pdf',
            'uploaded_by' => $user->id,
        ]);

        $attachment = $task->attachments()->first();
        $this->assertNotNull($attachment);
        Storage::disk('public')->assertExists($attachment->file_path);
    }

    public function test_task_attachment_rejects_disallowed_mime(): void
    {
        Storage::fake('public');

        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createTask($organization, $project, $user);

        $file = UploadedFile::fake()->create('payload.exe', 40, 'application/octet-stream');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.attachments.store', $task), [
                'file' => $file,
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('task_attachments', [
            'task_id' => $task->id,
            'file_name' => 'payload.exe',
        ]);
    }
}
