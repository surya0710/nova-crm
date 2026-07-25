<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectReport;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function apiHeaders(Organization $organization): array
    {
        return [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ];
    }

    protected function createProject(Organization $organization, User $owner, User $actor): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Reporting Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_api_generate_report_creates_report_record(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $project = $this->createProject($organization, $user, $user);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson(
            '/api/v1/projects/'.$project->id.'/reports',
            [
                'report_type' => 'summary',
                'format' => 'csv',
            ],
            $this->apiHeaders($organization),
        );

        $response->assertCreated();
        $response->assertJsonStructure(['data' => ['id', 'report_type', 'storage_path']]);

        $reportId = $response->json('data.id');
        $report = ProjectReport::query()->findOrFail($reportId);

        $this->assertNotNull($report->storage_path);
        Storage::assertExists($report->storage_path);
    }
}
