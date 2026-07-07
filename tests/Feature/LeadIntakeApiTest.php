<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadIntakeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setupApiUser(string $role = 'organization-owner'): array
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

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Prospect',
            'email' => 'jane@example.com',
            'phone' => '+1 555 0100',
            'source' => 'website',
            'form_type' => 'contact',
            'source_url' => 'https://example.com/contact',
            'service_interest' => 'student',
            'message' => 'Interested in studying abroad.',
            'custom_fields' => [
                'destination_country' => 'Canada',
                'travel_month' => '2026-09',
            ],
        ], $overrides);
    }

    public function test_successful_api_lead_creation(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/leads', $this->validPayload(), $this->apiHeaders($organization));

        $response->assertCreated();
        $response->assertJson([
            'success' => true,
            'message' => 'Lead created successfully.',
        ]);
        $response->assertJsonStructure(['lead_id']);

        $this->assertDatabaseHas('leads', [
            'id' => $response->json('lead_id'),
            'organization_id' => $organization->id,
            'name' => 'Jane Prospect',
            'email' => 'jane@example.com',
            'phone' => '+15550100',
            'source' => 'website',
            'status' => 'new',
            'created_by' => $user->id,
        ]);
    }

    public function test_invalid_payload_returns_422(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/leads', [
            'email' => 'not-an-email',
        ], $this->apiHeaders($organization));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'phone', 'source']);
    }

    public function test_unauthorized_request_returns_401(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->postJson('/api/v1/leads', $this->validPayload(), $this->apiHeaders($organization));

        $response->assertUnauthorized();
    }

    public function test_duplicate_detection_returns_409(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'jane@example.com',
            'phone' => '+15550100',
            'status' => 'new',
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/leads', $this->validPayload(), $this->apiHeaders($organization));

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'message' => 'A lead with this email or phone already exists.',
        ]);
        $response->assertJsonStructure(['lead_id']);
    }

    public function test_organization_isolation(): void
    {
        [$userA, $orgA] = $this->setupApiUser('manager');
        $orgB = Organization::factory()->create(['name' => 'Other Org']);

        Sanctum::actingAs($userA, ['*']);

        $response = $this->postJson('/api/v1/leads', $this->validPayload([
            'email' => 'isolated@example.com',
        ]), $this->apiHeaders($orgB));

        $response->assertCreated();

        $this->assertDatabaseHas('leads', [
            'email' => 'isolated@example.com',
            'organization_id' => $orgA->id,
        ]);

        $this->assertDatabaseMissing('leads', [
            'email' => 'isolated@example.com',
            'organization_id' => $orgB->id,
        ]);
    }

    public function test_custom_fields_are_stored(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/leads', $this->validPayload([
            'custom_fields' => [
                'visa_type' => 'student',
                'destination_country' => 'Canada',
                'relationship' => 'Brother',
            ],
        ]), $this->apiHeaders($organization));

        $response->assertCreated();

        $lead = Lead::query()->find($response->json('lead_id'));

        $this->assertEquals('Student Visa', $lead->custom_fields['visa_type']);
        $this->assertEquals('Canada', $lead->custom_fields['destination_country']);
        $this->assertEquals('Brother', $lead->custom_fields['relationship']);
        $this->assertEquals('contact', $lead->custom_fields['form_type']);
        $this->assertEquals('https://example.com/contact', $lead->custom_fields['source_url']);
        $this->assertEquals('Student Visa', $lead->custom_fields['service_interest']);
    }

    public function test_notifications_sent_for_api_leads(): void
    {
        Notification::fake();

        [$owner, $organization] = $this->setupApiUser('organization-owner');
        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');

        Sanctum::actingAs($owner, ['*']);

        $response = $this->postJson('/api/v1/leads', $this->validPayload(), $this->apiHeaders($organization));

        $response->assertCreated();

        Notification::assertSentTo($manager, CrmNotification::class, function (CrmNotification $notification) use ($organization) {
            return $notification->title === 'New API lead'
                && $notification->organizationId === $organization->id;
        });
    }

    public function test_assigned_user_is_notified(): void
    {
        Notification::fake();

        [$owner, $organization] = $this->setupApiUser('organization-owner');
        $assignee = User::factory()->create();
        $organization->addMember($assignee, 'sales-executive');

        Sanctum::actingAs($owner, ['*']);

        $response = $this->postJson('/api/v1/leads', $this->validPayload([
            'assigned_to' => $assignee->id,
        ]), $this->apiHeaders($organization));

        $response->assertCreated();

        Notification::assertSentTo($assignee, CrmNotification::class, function (CrmNotification $notification) {
            return $notification->title === 'New API lead';
        });
    }

    public function test_audit_logs_are_written(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/leads', $this->validPayload(), $this->apiHeaders($organization));

        $response->assertCreated();

        $leadId = $response->json('lead_id');

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'auditable_type' => Lead::class,
            'auditable_id' => $leadId,
            'event' => 'created',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'auditable_type' => Lead::class,
            'auditable_id' => $leadId,
            'event' => 'received_via_api',
        ]);

        $apiAudit = AuditLog::query()
            ->where('auditable_id', $leadId)
            ->where('event', 'received_via_api')
            ->first();

        $this->assertSame('website', $apiAudit->properties['source']);
        $this->assertSame('contact', $apiAudit->properties['form_type']);
        $this->assertSame('https://example.com/contact', $apiAudit->properties['source_url']);
    }

    public function test_rate_limiting_blocks_excessive_requests(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');

        Sanctum::actingAs($user, ['*']);

        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/api/v1/leads', $this->validPayload([
                'email' => "lead{$i}@example.com",
                'phone' => '+1555010'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ]), $this->apiHeaders($organization))->assertCreated();
        }

        $response = $this->postJson('/api/v1/leads', $this->validPayload([
            'email' => 'blocked@example.com',
            'phone' => '+15550999',
        ]), $this->apiHeaders($organization));

        $response->assertStatus(429);
    }

    public function test_normalization_maps_visa_types_and_phone(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/leads', $this->validPayload([
            'phone' => '(555) 123-4567',
            'service_interest' => 'visitor',
            'custom_fields' => [
                'visa_type' => 'visitor',
            ],
        ]), $this->apiHeaders($organization));

        $response->assertCreated();

        $lead = Lead::query()->find($response->json('lead_id'));

        $this->assertSame('5551234567', $lead->phone);
        $this->assertSame('Visitor Visa', $lead->custom_fields['visa_type']);
        $this->assertSame('Visitor Visa', $lead->custom_fields['service_interest']);
    }

    public function test_message_is_stored_as_lead_note(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/leads', $this->validPayload(), $this->apiHeaders($organization));

        $response->assertCreated();

        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => $response->json('lead_id'),
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'body' => 'Interested in studying abroad.',
        ]);
    }

    public function test_user_without_api_access_is_forbidden(): void
    {
        [$user, $organization] = $this->setupApiUser('sales-executive');

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/leads', $this->validPayload(), $this->apiHeaders($organization));

        $response->assertForbidden();
    }
}
