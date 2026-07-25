<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMention;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Services\MentionService;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MentionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setupOrg(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        return [$user, $organization];
    }

    public function test_extract_mentions_returns_unique_handles(): void
    {
        $service = app(MentionService::class);

        $handles = $service->extractMentions('Hey @jane and @bob, also @jane again and @alice.smith!');

        $this->assertSame(['jane', 'bob', 'alice.smith'], $handles);
    }

    public function test_record_mentions_creates_rows_and_notifies(): void
    {
        [$actor, $organization] = $this->setupOrg();
        $mentioned = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
        $organization->addMember($mentioned, 'employee');

        $project = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Mention Project',
            'owner_id' => $actor->id,
            'manager_id' => $actor->id,
            'priority' => 'medium',
        ], $actor);

        app(TaskDefaultsService::class)->seedAll($organization);
        $task = Task::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Mention Task',
            'slug' => 'mention-task',
            'task_number' => 'TASK-0001',
            'status' => 'pending',
            'priority' => 'medium',
            'created_by' => $actor->id,
            'is_archived' => false,
            'completion_percentage' => 0,
        ]);

        Notification::fake();

        $mentions = app(MentionService::class)->recordMentions(
            $organization,
            $project,
            $task,
            'task_comment',
            99,
            'Please review @jane',
            $actor,
        );

        $this->assertCount(1, $mentions);
        $this->assertInstanceOf(ProjectMention::class, $mentions->first());
        $this->assertDatabaseHas('project_mentions', [
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'task_id' => $task->id,
            'mentioned_user_id' => $mentioned->id,
            'mentioned_by' => $actor->id,
        ]);

        Notification::assertSentTo($mentioned, CrmNotification::class);
    }
}
