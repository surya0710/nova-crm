<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectReport;
use App\Models\User;
use App\Services\ProjectReportingService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProjectReportingServiceTest extends TestCase
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
            'name' => 'Report Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    #[DataProvider('formatProvider')]
    public function test_generate_report_creates_project_report_with_storage_path(string $format, string $extension): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $project = $this->createProject($organization, $user, $user);
        $service = app(ProjectReportingService::class);

        $report = $service->generate(
            $project,
            $organization,
            'summary',
            $format,
            [],
            $user,
        );

        $this->assertInstanceOf(ProjectReport::class, $report);
        $this->assertNotNull($report->storage_path);
        $this->assertStringEndsWith('.'.$extension, $report->storage_path);

        $this->assertDatabaseHas('project_reports', [
            'id' => $report->id,
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'report_type' => 'summary',
            'generated_by' => $user->id,
        ]);

        Storage::assertExists($report->storage_path);
    }

    public static function formatProvider(): array
    {
        return [
            'csv' => ['csv', 'csv'],
            'pdf' => ['pdf', 'pdf'],
            'excel' => ['excel', 'xlsx'],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }
}
