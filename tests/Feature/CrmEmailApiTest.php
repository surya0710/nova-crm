<?php

namespace Tests\Feature;

use App\Models\CrmEmailConversation;
use App\Models\CrmEmailMessage;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\DashboardWidgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ConfiguresOrganizationMail;
use Tests\TestCase;

class CrmEmailApiTest extends TestCase
{
    use ConfiguresOrganizationMail;
    use RefreshDatabase;

    public function test_send_history_templates_conversations_and_metrics(): void
    {
        Mail::fake();
        app(DashboardWidgetService::class)->seedSystemWidgets();

        $user = User::factory()->create(['email' => 'sender@acme.test']);
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $organization->addMember($user, 'manager');
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'client@example.com',
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user, ['*']);
        $headers = [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ];

        $this->postJson('/api/v1/crm/email/templates', [
            'name' => 'Welcome',
            'subject' => 'Hello {{customer.name}}',
            'body' => 'Welcome {{customer.email}}',
            'category' => 'general',
            'is_active' => true,
        ], $headers)->assertCreated()->assertJsonPath('success', true);

        $this->getJson('/api/v1/crm/email/templates', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Welcome');

        $this->postJson('/api/v1/crm/email/send', [
            'related_type' => 'customer',
            'related_id' => $customer->id,
            'email' => 'client@example.com',
            'cc' => 'archive@acme.test',
            'subject' => 'Quote follow-up',
            'message' => 'Checking in',
        ], $headers)->assertSuccessful()->assertJsonMissing(['password', 'smtp_password', 'signing_secret']);

        $this->assertDatabaseHas('crm_email_messages', [
            'organization_id' => $organization->id,
            'subject' => 'Quote follow-up',
        ]);

        $list = $this->getJson('/api/v1/crm/email/messages', $headers)->assertOk();
        $list->assertJsonPath('data.0.subject', 'Quote follow-up');
        $this->assertArrayNotHasKey('attachment_paths', $list->json('data.0'));
        $this->assertArrayNotHasKey('provider_metadata', $list->json('data.0'));
        $this->assertArrayNotHasKey('idempotency_key', $list->json('data.0'));

        $messageId = CrmEmailMessage::query()->value('id');
        $this->getJson('/api/v1/crm/email/messages/'.$messageId, $headers)
            ->assertOk()
            ->assertJsonPath('data.id', $messageId);

        $this->getJson('/api/v1/crm/email/conversations', $headers)->assertOk();
        $conversationId = CrmEmailConversation::query()->value('id');
        $this->getJson('/api/v1/crm/email/conversations/'.$conversationId, $headers)->assertOk();
        $this->getJson('/api/v1/crm/email/conversations/'.$conversationId.'/messages', $headers)->assertOk();

        $this->getJson('/api/v1/crm/email/metrics?from='.now()->subDay()->toDateString().'&to='.now()->toDateString(), $headers)
            ->assertOk()
            ->assertJsonStructure([
                'emails_sent', 'emails_queued', 'emails_delivered', 'emails_failed', 'emails_bounced',
                'delivery_rate', 'failure_rate', 'by_salesperson', 'by_customer', 'by_template', 'by_date', 'by_opportunity',
            ]);

        $this->getJson('/api/v1/dashboard/widgets/crm_email_metrics/data', $headers)
            ->assertOk()
            ->assertJsonStructure(['emails_sent', 'emails_queued', 'delivery_rate']);
    }

    public function test_api_is_tenant_isolated_and_hides_other_org_records(): void
    {
        $userA = User::factory()->create();
        $orgA = Organization::factory()->create();
        $orgA->addMember($userA, 'manager');

        $userB = User::factory()->create();
        $orgB = Organization::factory()->create();
        $orgB->addMember($userB, 'manager');

        $foreign = CrmEmailMessage::withoutGlobalScopes()->create([
            'organization_id' => $orgA->id,
            'related_type' => Customer::class,
            'related_id' => 1,
            'to' => ['hidden@example.com'],
            'subject' => 'Secret',
            'status' => 'sent',
        ]);

        Sanctum::actingAs($userB, ['*']);

        $this->getJson('/api/v1/crm/email/messages/'.$foreign->id, [
            'X-Organization-Id' => (string) $orgB->id,
            'Accept' => 'application/json',
        ])->assertNotFound();
    }
}
