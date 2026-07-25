<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportSessionJob;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\ImportSession;
use App\Models\Organization;
use App\Models\User;
use App\Services\Import\ImportCatalogService;
use App\Services\Import\ImportEntityRegistry;
use App\Services\Import\ImportPlatformService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImportCenterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function setupOwner(string $plan = 'enterprise'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => $plan]);
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        return [$user, $organization];
    }

    public function test_all_catalog_entities_are_registered(): void
    {
        $registry = app(ImportEntityRegistry::class);

        foreach (array_keys(config('import.entities')) as $type) {
            $this->assertTrue($registry->has($type), "Missing adapter for [{$type}]");
        }
    }

    public function test_import_center_index_lists_entities_for_owner(): void
    {
        [$user, $organization] = $this->setupOwner();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('administration.imports.index'));

        $response->assertOk();
        $response->assertSee('Import Center');
        $response->assertSee('Departments');
        $response->assertSee('Employees');
        $response->assertSee('Leads');
    }

    public function test_employee_without_import_permission_is_forbidden(): void
    {
        [, $organization] = $this->setupOwner();
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');
        app(TenantContext::class)->set($organization);

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('administration.imports.index'))
            ->assertForbidden();
    }

    public function test_department_csv_import_create_and_audit(): void
    {
        Storage::fake('local');
        [$user, $organization] = $this->setupOwner();
        $imports = app(ImportPlatformService::class);

        $file = UploadedFile::fake()->createWithContent(
            'departments.csv',
            "Name,Code\nEngineering,ENG\nPeople Ops,HR\n"
        );

        $session = $imports->upload($organization, 'department', $file, $user);
        $session->forceFill([
            'metadata' => ['duplicate_strategy' => 'skip'],
        ])->save();
        $session = $imports->validate($session->fresh(), $user);
        $this->assertSame(ImportSession::STATUS_READY, $session->status);

        $session = $imports->startImport($session, $user);

        $this->assertSame(ImportSession::STATUS_COMPLETED, $session->status);
        $this->assertSame(2, $session->created_count);
        $this->assertSame(2, Department::query()->where('organization_id', $organization->id)->count());
        $this->assertDatabaseHas('hrms_departments', [
            'organization_id' => $organization->id,
            'code' => 'ENG',
            'name' => 'Engineering',
        ]);
        $this->assertTrue(
            AuditLog::query()
                ->where('auditable_id', $session->id)
                ->where('event', 'import_started')
                ->exists()
        );
    }

    public function test_duplicate_strategy_skip_reports_existing_department(): void
    {
        Storage::fake('local');
        [$user, $organization] = $this->setupOwner();
        Department::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Engineering',
            'code' => 'ENG',
        ]);

        $imports = app(ImportPlatformService::class);
        $file = UploadedFile::fake()->createWithContent(
            'departments.csv',
            "Name,Code\nEngineering,ENG\n"
        );

        $session = $imports->upload($organization, 'department', $file, $user);
        $session->forceFill(['metadata' => ['duplicate_strategy' => 'skip']])->save();
        $session = $imports->validate($session->fresh(), $user);

        $this->assertGreaterThan(0, $session->failed_count);
        $preview = $imports->preview($session, $user);
        $this->assertSame(0, $preview->validRows);
    }

    public function test_field_mapping_can_be_applied(): void
    {
        Storage::fake('local');
        [$user, $organization] = $this->setupOwner();
        $imports = app(ImportPlatformService::class);

        $file = UploadedFile::fake()->createWithContent(
            'departments.csv',
            "Dept Name,Dept Code\nFinance,FIN\n"
        );

        $session = $imports->upload($organization, 'department', $file, $user);
        $session = $imports->applyMapping($session, [
            'name' => 'Dept Name',
            'code' => 'Dept Code',
        ], $user);

        $this->assertSame(ImportSession::STATUS_READY, $session->status);
        $this->assertSame('Dept Name', $session->column_mapping['name']);
        $this->assertSame(1, $session->total_rows - $session->failed_count);
    }

    public function test_large_import_is_queued(): void
    {
        Storage::fake('local');
        Queue::fake();
        config(['import.queue_threshold_rows' => 1]);

        [$user, $organization] = $this->setupOwner();
        $imports = app(ImportPlatformService::class);

        $file = UploadedFile::fake()->createWithContent(
            'departments.csv',
            "Name,Code\nA,A1\nB,B1\n"
        );

        $session = $imports->upload($organization, 'department', $file, $user);
        $session = $imports->validate($session, $user);
        $imports->startImport($session, $user);

        Queue::assertPushed(ProcessImportSessionJob::class);
    }

    public function test_organization_isolation_on_import_center_show(): void
    {
        Storage::fake('local');
        [$userA, $orgA] = $this->setupOwner();
        $imports = app(ImportPlatformService::class);

        $file = UploadedFile::fake()->createWithContent('d.csv', "Name,Code\nOps,OPS\n");
        $session = $imports->upload($orgA, 'department', $file, $userA);

        $userB = User::factory()->create();
        $orgB = Organization::factory()->create(['plan' => 'enterprise']);
        $orgB->addMember($userB, 'organization-owner');
        app(TenantContext::class)->set($orgB);

        $this->actingAs($userB)
            ->withSession(['current_organization_id' => $orgB->id])
            ->get(route('administration.imports.show', $session))
            ->assertNotFound();
    }

    public function test_catalog_service_respects_module_permission(): void
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');
        app(TenantContext::class)->set($organization);

        $groups = app(ImportCatalogService::class)->groupedFor($manager, $organization);

        $this->assertArrayHasKey('crm', $groups);
        $this->assertTrue($manager->hasPermission('imports.crm', $organization));
        $this->assertTrue($manager->hasPermission('imports.hrms', $organization));
    }

    public function test_api_catalog_requires_authentication(): void
    {
        $this->getJson('/api/v1/imports/catalog')->assertUnauthorized();
    }

    public function test_api_upload_and_preview_flow(): void
    {
        Storage::fake('local');
        [$user, $organization] = $this->setupOwner();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->createWithContent(
            'departments.csv',
            "Name,Code\nLegal,LEG\n"
        );

        $upload = $this->post('/api/v1/imports/department/upload', [
            'file' => $file,
            'duplicate_strategy' => 'skip',
        ], [
            'Accept' => 'application/json',
            'X-Organization-Id' => (string) $organization->id,
        ]);

        $upload->assertCreated();
        $sessionId = $upload->json('session.id');
        $this->assertNotNull($sessionId);

        $this->getJson("/api/v1/imports/sessions/{$sessionId}/preview", [
            'X-Organization-Id' => (string) $organization->id,
        ])
            ->assertOk()
            ->assertJsonPath('preview.valid_rows', 1);
    }
}
