<?php

namespace Tests\Feature;

use App\Jobs\ProcessExportSessionJob;
use App\Models\ExportSession;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\Export\ExportDefinitionRegistry;
use App\Services\Export\ExportPlatformService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExportCenterTest extends TestCase
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

    public function test_entities_are_registered(): void
    {
        $registry = app(ExportDefinitionRegistry::class);

        $this->assertTrue($registry->has('lead'));
        $this->assertTrue($registry->has('employee'));
        $this->assertTrue($registry->has('campaign'));
        $this->assertTrue($registry->has('user'));
        $this->assertTrue($registry->has('role'));
    }

    public function test_csv_export_selected_records_and_audits(): void
    {
        Storage::fake('local');
        config(['export.disk' => 'local', 'export.queue_threshold_rows' => 1000]);

        [$user, $organization] = $this->setupOwner();
        $leads = Lead::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'status' => 'new',
        ]);

        $session = app(ExportPlatformService::class)->start(
            $organization,
            $user,
            'lead',
            'csv',
            ['mode' => 'ids', 'ids' => $leads->pluck('id')->all()],
            ['name', 'email', 'status'],
        );

        $this->assertSame(ExportSession::STATUS_COMPLETED, $session->status);
        $this->assertSame(3, $session->processed_count);
        $this->assertTrue($session->isDownloadable());
        Storage::disk('local')->assertExists($session->file_path);

        $contents = Storage::disk('local')->get($session->file_path);
        $this->assertStringContainsString('Name', $contents);
        $this->assertStringContainsString('Email', $contents);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $session->id,
            'event' => 'export_completed',
        ]);
    }

    public function test_xlsx_and_pdf_exports_generate_files(): void
    {
        Storage::fake('local');
        config(['export.disk' => 'local', 'export.queue_threshold_rows' => 1000]);

        [$user, $organization] = $this->setupOwner();
        $lead = Lead::factory()->create(['organization_id' => $organization->id]);

        $xlsx = app(ExportPlatformService::class)->start(
            $organization,
            $user,
            'lead',
            'xlsx',
            ['mode' => 'ids', 'ids' => [$lead->id]],
        );
        $this->assertSame(ExportSession::STATUS_COMPLETED, $xlsx->status);
        Storage::disk('local')->assertExists($xlsx->file_path);

        $pdf = app(ExportPlatformService::class)->start(
            $organization,
            $user,
            'lead',
            'pdf',
            ['mode' => 'ids', 'ids' => [$lead->id]],
        );
        $this->assertSame(ExportSession::STATUS_COMPLETED, $pdf->status);
        Storage::disk('local')->assertExists($pdf->file_path);
    }

    public function test_filtered_and_full_dataset_export(): void
    {
        Storage::fake('local');
        config(['export.disk' => 'local', 'export.queue_threshold_rows' => 1000]);

        [$user, $organization] = $this->setupOwner();
        Lead::factory()->create(['organization_id' => $organization->id, 'status' => 'new', 'name' => 'Alpha']);
        Lead::factory()->create(['organization_id' => $organization->id, 'status' => 'contacted', 'name' => 'Beta']);

        $filtered = app(ExportPlatformService::class)->start(
            $organization,
            $user,
            'lead',
            'csv',
            ['mode' => 'filtered', 'filters' => ['status' => 'new']],
            ['name', 'status'],
        );
        $this->assertSame(1, $filtered->processed_count);

        $full = app(ExportPlatformService::class)->start(
            $organization,
            $user,
            'lead',
            'csv',
            ['mode' => 'complete'],
            ['name', 'status'],
        );
        $this->assertSame(2, $full->processed_count);
    }

    public function test_large_export_is_queued(): void
    {
        Queue::fake();
        config(['export.queue_threshold_rows' => 2]);

        [$user, $organization] = $this->setupOwner();
        $leads = Lead::factory()->count(3)->create(['organization_id' => $organization->id]);

        $session = app(ExportPlatformService::class)->start(
            $organization,
            $user,
            'lead',
            'csv',
            ['mode' => 'ids', 'ids' => $leads->pluck('id')->all()],
        );

        $this->assertSame(ExportSession::STATUS_QUEUED, $session->status);
        Queue::assertPushed(ProcessExportSessionJob::class);
    }

    public function test_download_is_organization_isolated(): void
    {
        Storage::fake('local');
        config(['export.disk' => 'local', 'export.queue_threshold_rows' => 1000]);

        [$userA, $orgA] = $this->setupOwner();
        $lead = Lead::factory()->create(['organization_id' => $orgA->id]);

        $session = app(ExportPlatformService::class)->start(
            $orgA,
            $userA,
            'lead',
            'csv',
            ['mode' => 'ids', 'ids' => [$lead->id]],
        );

        $userB = User::factory()->create();
        $orgB = Organization::factory()->create(['plan' => 'enterprise']);
        $orgB->addMember($userB, 'organization-owner');
        app(TenantContext::class)->set($orgB);

        $this->actingAs($userB)
            ->withSession(['current_organization_id' => $orgB->id])
            ->get(route('administration.exports.show', $session))
            ->assertNotFound();
    }

    public function test_sensitive_password_column_never_exported(): void
    {
        Storage::fake('local');
        config(['export.disk' => 'local', 'export.queue_threshold_rows' => 1000]);

        [$user, $organization] = $this->setupOwner();

        $session = app(ExportPlatformService::class)->start(
            $organization,
            $user,
            'user',
            'csv',
            ['mode' => 'ids', 'ids' => [$user->id]],
            ['name', 'email', 'password'],
        );

        $contents = Storage::disk('local')->get($session->file_path);
        $this->assertStringNotContainsString('Password', $contents);
        $this->assertStringNotContainsString($user->password, $contents);
    }

    public function test_revoke_blocks_download(): void
    {
        Storage::fake('local');
        config(['export.disk' => 'local', 'export.queue_threshold_rows' => 1000]);

        [$user, $organization] = $this->setupOwner();
        $lead = Lead::factory()->create(['organization_id' => $organization->id]);

        $exports = app(ExportPlatformService::class);
        $session = $exports->start(
            $organization,
            $user,
            'lead',
            'csv',
            ['mode' => 'ids', 'ids' => [$lead->id]],
        );

        $exports->revoke($session, $user);
        $session->refresh();

        $this->assertSame(ExportSession::STATUS_REVOKED, $session->status);
        $this->assertFalse($session->isDownloadable());
    }

    public function test_api_generate_and_status(): void
    {
        Storage::fake('local');
        config(['export.disk' => 'local', 'export.queue_threshold_rows' => 1000]);

        [$user, $organization] = $this->setupOwner();
        $lead = Lead::factory()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/exports/generate', [
            'entity_type' => 'lead',
            'format' => 'csv',
            'selection_mode' => 'ids',
            'ids' => [$lead->id],
            'columns' => ['name', 'email'],
        ], [
            'X-Organization-Id' => (string) $organization->id,
        ]);

        $response->assertCreated();
        $sessionId = $response->json('session.id');
        $this->assertNotNull($sessionId);

        $this->getJson('/api/v1/exports/sessions/'.$sessionId, [
            'X-Organization-Id' => (string) $organization->id,
        ])
            ->assertOk()
            ->assertJsonPath('session.status', ExportSession::STATUS_COMPLETED);
    }

    public function test_export_center_index_requires_permission_context(): void
    {
        [$user, $organization] = $this->setupOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('administration.exports.index'))
            ->assertOk();
    }

    public function test_bulk_toolbar_export_endpoint_accepts_selection(): void
    {
        Storage::fake('local');
        config(['export.disk' => 'local', 'export.queue_threshold_rows' => 1000]);

        [$user, $organization] = $this->setupOwner();
        $lead = Lead::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->postJson(route('administration.exports.store'), [
                'entity_type' => 'lead',
                'format' => 'csv',
                'selection_mode' => 'ids',
                'ids' => [$lead->id],
            ])
            ->assertCreated();
    }
}
