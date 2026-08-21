<?php

namespace Tests\Feature;

use App\Models\CrmEmailConversation;
use App\Models\CrmEmailMessage;
use App\Models\CrmEmailTemplate;
use App\Models\CrmEmailWebhookEndpoint;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationMailConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ConfiguresOrganizationMail;
use Tests\TestCase;

class CrmEmailSecurityTest extends TestCase
{
    use ConfiguresOrganizationMail;
    use RefreshDatabase;

    public function test_mail_config_templates_messages_conversations_and_webhooks_are_org_scoped(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'manager');
        $this->configureOrganizationMail($organization, 'smtp');

        $otherUser = User::factory()->create();
        $other = Organization::factory()->create();
        $other->addMember($otherUser, 'manager');
        $this->configureOrganizationMail($other, 'log');

        $this->assertSame('smtp', app(OrganizationMailConfig::class)->for($organization)->provider());
        $this->assertSame('log', app(OrganizationMailConfig::class)->for($other)->provider());
        $orgSettings = app(OrganizationMailConfig::class)->for($organization)->toSettingsArray();
        $otherSettings = app(OrganizationMailConfig::class)->for($other)->toSettingsArray();
        $this->assertTrue($orgSettings['has_password']);
        $this->assertArrayNotHasKey('password', $orgSettings);
        $this->assertNotSame($orgSettings['username'], $otherSettings['username'] ?? null);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $conversation = CrmEmailConversation::query()->create([
            'organization_id' => $other->id,
            'related_type' => Customer::class,
            'related_id' => $customer->id,
            'subject' => 'Other org thread',
            'thread_id' => 'thread-other',
            'message_count' => 1,
            'last_status' => 'sent',
            'last_message_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('crm.communications.show', $conversation))
            ->assertNotFound();

        Sanctum::actingAs($user, ['*']);
        $this->getJson('/api/v1/crm/email/conversations/'.$conversation->id, [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ])->assertNotFound();

        $endpoint = CrmEmailWebhookEndpoint::query()->create([
            'organization_id' => $other->id,
            'provider' => 'sendgrid',
            'token' => 'secret-token-other',
            'signing_secret' => 'sg-secret',
            'is_active' => true,
        ]);

        $this->postJson(route('webhooks.email', ['provider' => 'sendgrid', 'token' => $endpoint->token]), [
            ['email' => 'a@example.com', 'event' => 'delivered', 'sg_event_id' => 'x'],
        ])->assertUnauthorized();
    }

    public function test_sales_executive_cannot_manage_templates_over_api(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'sales-executive');

        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/crm/email/templates', [
            'name' => 'Blocked',
            'subject' => 'Hi',
            'body' => 'Body',
            'category' => 'general',
        ], [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ])->assertForbidden();
    }

    public function test_api_message_payload_omits_secrets(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'manager');

        $message = CrmEmailMessage::query()->create([
            'organization_id' => $organization->id,
            'related_type' => Customer::class,
            'related_id' => 1,
            'to' => ['client@example.com'],
            'subject' => 'Hello',
            'status' => 'sent',
            'attachment_paths' => ['crm-email/1/1/secret.pdf'],
            'provider_metadata' => ['raw' => 'provider-secret'],
            'idempotency_key' => 'abc',
        ]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->getJson('/api/v1/crm/email/messages/'.$message->id, [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $json = $response->json();
        $encoded = json_encode($json);
        $this->assertStringNotContainsString('secret.pdf', $encoded);
        $this->assertStringNotContainsString('provider-secret', $encoded);
        $this->assertStringNotContainsString('smtp_password', $encoded);
        $this->assertArrayNotHasKey('idempotency_key', $json['data'] ?? []);
    }
}
