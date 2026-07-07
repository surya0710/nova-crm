<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase5Test extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_creating_lead_writes_audit_log(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.store'), [
                'name' => 'Audit Lead',
                'source' => 'website',
                'status' => 'new',
                'priority' => 'medium',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'event' => 'created',
            'subject' => 'Audit Lead',
        ]);
    }

    public function test_user_with_audit_permission_can_view_audit_log(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Logged Lead',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('audit-logs.index'));

        $response->assertOk();
        $response->assertSee('Logged Lead');
    }

    public function test_global_search_finds_lead_by_name(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Unique Searchable Lead',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('search.index', ['q' => 'Unique Searchable']));

        $response->assertOk();
        $response->assertSee('Unique Searchable Lead');
    }

    public function test_user_can_upload_attachment_to_lead(): void
    {
        Storage::fake('public');

        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $file = UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('attachments.store'), [
                'attachable_type' => 'lead',
                'attachable_id' => $lead->id,
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'attachment-uploaded');

        $this->assertDatabaseHas('attachments', [
            'organization_id' => $organization->id,
            'attachable_type' => Lead::class,
            'attachable_id' => $lead->id,
            'original_name' => 'proposal.pdf',
        ]);
    }

    public function test_assigning_lead_notifies_assignee(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $assignee = User::factory()->create();
        $organization->addMember($assignee, 'sales-executive');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('leads.update', $lead), [
                'name' => $lead->name,
                'source' => $lead->source ?? 'website',
                'status' => $lead->status,
                'priority' => $lead->priority,
                'assigned_to' => $assignee->id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $assignee->id,
        ]);
    }

    public function test_api_token_allows_accessing_leads(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'API Lead',
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/leads');

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'API Lead');
    }

    public function test_user_can_create_api_token_from_settings(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('api-tokens.store'), [
                'name' => 'Integration Token',
            ]);

        $response->assertRedirect(route('api-tokens.index'));
        $response->assertSessionHas('status', 'api-token-created');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'Integration Token',
        ]);
    }
}
