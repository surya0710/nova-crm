<?php

namespace Tests\Feature;

use App\Models\AssignmentHistory;
use App\Models\AssignmentPool;
use App\Models\AssignmentPoolMember;
use App\Models\AssignmentRule;
use App\Models\ImportSession;
use App\Models\Lead;
use App\Models\MarketingProvider;
use App\Models\Organization;
use App\Models\User;
use App\Services\Import\ImportPlatformService;
use App\Services\MarketingProviderService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssignmentLeadIntakeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization, 2: User}
     */
    protected function setupWithAssigneePool(): array
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($owner, 'organization-owner');

        $assignee = User::factory()->create(['email' => 'assignee@example.com', 'name' => 'Pool Assignee']);
        $organization->addMember($assignee, 'sales-executive');

        app(TenantContext::class)->set($organization);

        $pool = AssignmentPool::factory()->forOrganization($organization)->strategy('round_robin')->create();
        AssignmentPoolMember::factory()->forPool($pool)->forUser($assignee)->create();
        AssignmentRule::factory()->forOrganization($organization)->forPool($pool)->defaultRule()->create();

        return [$owner, $organization, $assignee];
    }

    public function test_lead_import_blank_owner_uses_assignment_platform(): void
    {
        Storage::fake('local');
        [$owner, $organization, $assignee] = $this->setupWithAssigneePool();

        $file = UploadedFile::fake()->createWithContent(
            'leads.csv',
            "Full Name,Email,Source,Status,Owner\n".
            "Import Lead,import-blank@example.com,Website,New,\n"
        );

        $imports = app(ImportPlatformService::class);
        $session = $imports->upload($organization, 'lead', $file, $owner);
        $session = $imports->validate($session, $owner);
        $session = $imports->executeImport($session, $owner);

        $this->assertSame(ImportSession::STATUS_COMPLETED, $session->status);

        $lead = Lead::query()->where('email', 'import-blank@example.com')->first();
        $this->assertNotNull($lead);
        $this->assertSame($assignee->id, $lead->assigned_to);
        $this->assertDatabaseHas('assignment_histories', [
            'entity_id' => $lead->id,
            'reason' => AssignmentHistory::REASON_IMPORTED,
            'new_owner_id' => $assignee->id,
        ]);
    }

    public function test_lead_import_explicit_owner_bypasses_assignment(): void
    {
        Storage::fake('local');
        [$owner, $organization, $assignee] = $this->setupWithAssigneePool();

        $explicit = User::factory()->create(['email' => 'explicit@example.com', 'name' => 'Explicit Owner']);
        $organization->addMember($explicit, 'sales-executive');

        $file = UploadedFile::fake()->createWithContent(
            'leads.csv',
            "Full Name,Email,Source,Status,Owner\n".
            "Import Lead,import-owner@example.com,Website,New,explicit@example.com\n"
        );

        $imports = app(ImportPlatformService::class);
        $session = $imports->upload($organization, 'lead', $file, $owner);
        $session = $imports->validate($session, $owner);
        $session = $imports->executeImport($session, $owner);

        $lead = Lead::query()->where('email', 'import-owner@example.com')->first();
        $this->assertSame($explicit->id, $lead->assigned_to);
        $this->assertNotSame($assignee->id, $lead->assigned_to);
        $this->assertDatabaseMissing('assignment_histories', [
            'entity_id' => $lead->id,
        ]);
    }

    public function test_api_intake_without_owner_uses_assignment_platform(): void
    {
        [$owner, $organization, $assignee] = $this->setupWithAssigneePool();

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/leads', [
            'name' => 'API Auto Lead',
            'email' => 'api-auto@example.com',
            'phone' => '+15550109999',
            'source' => 'api',
        ], [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ]);

        $response->assertCreated();

        $lead = Lead::query()->where('email', 'api-auto@example.com')->first();
        $this->assertSame($assignee->id, $lead->assigned_to);
        $this->assertDatabaseHas('assignment_histories', [
            'entity_id' => $lead->id,
            'reason' => AssignmentHistory::REASON_API,
        ]);
    }

    public function test_marketing_import_uses_assignment_platform(): void
    {
        [$owner, $organization, $assignee] = $this->setupWithAssigneePool();

        $provider = MarketingProvider::factory()->create([
            'organization_id' => $organization->id,
            'slug' => 'meta',
            'status' => MarketingProvider::STATUS_CONNECTED,
        ]);

        $service = app(MarketingProviderService::class);
        $method = new \ReflectionMethod($service, 'importNormalizedEntry');
        $method->setAccessible(true);

        $result = $method->invoke($service, $provider, [
            'external_lead_id' => 'ext-assign-1',
            'external_form_id' => 'form-1',
            'fetch_ok' => true,
            'fields' => [
                'full_name' => 'Meta Lead',
                'email' => 'meta-assign@example.com',
            ],
            'raw' => [],
        ], $owner);

        $this->assertSame('imported', $result['result'], $result['error'] ?? 'unknown error');

        $lead = Lead::query()->find($result['lead_id']);
        $this->assertSame($assignee->id, $lead->assigned_to);
        $this->assertDatabaseHas('assignment_histories', [
            'entity_id' => $lead->id,
            'reason' => AssignmentHistory::REASON_IMPORTED,
        ]);
        $this->assertDatabaseHas('marketing_provider_imported_leads', [
            'lead_id' => $lead->id,
            'external_lead_id' => 'ext-assign-1',
        ]);
    }

    public function test_web_lead_create_without_owner_auto_assigns(): void
    {
        [$owner, $organization, $assignee] = $this->setupWithAssigneePool();

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.store'), [
                'name' => 'Web Auto Lead',
                'email' => 'web-auto@example.com',
                'source' => 'website',
                'status' => 'new',
                'priority' => 'medium',
            ])
            ->assertRedirect();

        $lead = Lead::query()->where('email', 'web-auto@example.com')->first();
        $this->assertSame($assignee->id, $lead->assigned_to);
    }
}
