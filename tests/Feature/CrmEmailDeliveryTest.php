<?php

namespace Tests\Feature;

use App\Jobs\SendCrmEmailJob;
use App\Mail\CrmMessageMail;
use App\Models\Contact;
use App\Models\CrmEmailConversation;
use App\Models\CrmEmailMessage;
use App\Models\CrmEmailWebhookEndpoint;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use App\Services\CrmEmailWebhookEndpointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\ConfiguresOrganizationMail;
use Tests\TestCase;

class CrmEmailDeliveryTest extends TestCase
{
    use ConfiguresOrganizationMail;
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization, 2: Customer, 3: Contact}
     */
    protected function setupCrm(): array
    {
        $user = User::factory()->create(['email' => 'sender@acme.test']);
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'manager');
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'billing@example.com',
            'created_by' => $user->id,
        ]);

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'email' => 'person@example.com',
            'created_by' => $user->id,
        ]);

        return [$user, $organization, $customer, $contact];
    }

    public function test_smtp_send_is_sent_not_delivered(): void
    {
        Mail::fake();

        [$user, $organization, , $contact] = $this->setupCrm();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.send', $contact), [
                'email' => 'person@example.com',
                'subject' => 'Hello',
                'message' => 'Body',
            ])
            ->assertRedirect();

        $message = CrmEmailMessage::query()->first();
        $this->assertNotNull($message);
        $this->assertSame('sent', $message->status);
        $this->assertSame('log', $message->provider);
        $this->assertNotNull($message->rfc_message_id);
        $this->assertNotNull($message->sent_at);
        $this->assertNull($message->delivered_at);
        $this->assertFalse($message->supportsDeliveryTracking());
        $this->assertNotNull($message->conversation_id);
    }

    public function test_queue_dispatch_is_idempotent_after_send(): void
    {
        Mail::fake();

        [$user, $organization, , $contact] = $this->setupCrm();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.send', $contact), [
                'email' => 'person@example.com',
                'subject' => 'Hello',
                'message' => 'Body',
            ]);

        $message = CrmEmailMessage::query()->first();
        $this->assertSame('sent', $message->status);

        SendCrmEmailJob::dispatchSync($message->id);

        Mail::assertSent(CrmMessageMail::class, 1);
        $this->assertSame('sent', $message->fresh()->status);
    }

    public function test_async_queue_returns_queued_status(): void
    {
        Queue::fake();
        Mail::fake();

        [$user, $organization, , $contact] = $this->setupCrm();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.send', $contact), [
                'email' => 'person@example.com',
                'subject' => 'Hello',
                'message' => 'Body',
            ])
            ->assertRedirect();

        Queue::assertPushed(SendCrmEmailJob::class);
        $this->assertSame('queued', CrmEmailMessage::query()->value('status'));
        Mail::assertNothingSent();
    }

    public function test_sendgrid_webhook_marks_delivered_for_matching_org_only(): void
    {
        Mail::fake();

        [$user, $organization, , $contact] = $this->setupCrm();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.send', $contact), [
                'email' => 'person@example.com',
                'subject' => 'Tracked',
                'message' => 'Body',
            ]);

        $message = CrmEmailMessage::query()->first();
        $message->forceFill(['provider' => 'sendgrid'])->save();

        $other = Organization::factory()->create();

        $endpoint = app(CrmEmailWebhookEndpointService::class)->ensure($organization->fresh(), 'sendgrid');
        $this->assertNotNull($endpoint);

        $otherEndpoint = CrmEmailWebhookEndpoint::query()->create([
            'organization_id' => $other->id,
            'provider' => 'sendgrid',
            'token' => 'other-token-'.uniqid(),
            'signing_secret' => 'other-secret',
            'is_active' => true,
        ]);

        $payload = [[
            'event' => 'delivered',
            'sg_event_id' => 'evt-1',
            'sg_message_id' => 'sg-abc',
            'smtp-id' => $message->rfc_message_id,
            'konnect_email_id' => $message->id,
            'timestamp' => time(),
        ]];

        $this->withHeaders(['Authorization' => 'Bearer '.$endpoint->decryptedSigningSecret()])
            ->postJson(route('webhooks.email', ['provider' => 'sendgrid', 'token' => $endpoint->token]), $payload)
            ->assertOk();

        $this->assertSame('delivered', $message->fresh()->status);
        $this->assertNotNull($message->fresh()->delivered_at);

        $this->withHeaders(['Authorization' => 'Bearer '.$otherEndpoint->decryptedSigningSecret()])
            ->postJson(route('webhooks.email', ['provider' => 'sendgrid', 'token' => $otherEndpoint->token]), $payload)
            ->assertOk();

        $this->assertSame('delivered', $message->fresh()->status);
        $this->assertSame(0, CrmEmailMessage::withoutGlobalScopes()->where('organization_id', $other->id)->count());
    }

    public function test_duplicate_webhook_is_ignored(): void
    {
        Mail::fake();

        [$user, $organization, , $contact] = $this->setupCrm();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.send', $contact), [
                'email' => 'person@example.com',
                'subject' => 'Tracked',
                'message' => 'Body',
            ]);

        $message = CrmEmailMessage::query()->first();
        $message->forceFill(['provider' => 'sendgrid'])->save();
        $endpoint = app(CrmEmailWebhookEndpointService::class)->ensure($organization->fresh(), 'sendgrid');

        $payload = [[
            'event' => 'delivered',
            'sg_event_id' => 'evt-dup',
            'konnect_email_id' => $message->id,
        ]];

        $headers = ['Authorization' => 'Bearer '.$endpoint->decryptedSigningSecret()];

        $this->withHeaders($headers)
            ->postJson(route('webhooks.email', ['provider' => 'sendgrid', 'token' => $endpoint->token]), $payload)
            ->assertOk();

        $this->withHeaders($headers)
            ->postJson(route('webhooks.email', ['provider' => 'sendgrid', 'token' => $endpoint->token]), $payload)
            ->assertOk();

        $this->assertSame(1, $message->webhookEvents()->count());
    }

    public function test_smtp_provider_does_not_accept_fabricated_delivery(): void
    {
        Mail::fake();

        [$user, $organization, , $contact] = $this->setupCrm();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.send', $contact), [
                'email' => 'person@example.com',
                'subject' => 'Hello',
                'message' => 'Body',
            ]);

        $message = CrmEmailMessage::query()->first();
        $applied = app(\App\Services\CrmEmailDeliveryService::class)->markDelivered($message);

        $this->assertFalse($applied);
        $this->assertSame('sent', $message->fresh()->status);
        $this->assertNull($message->fresh()->delivered_at);
    }

    public function test_replies_join_the_same_conversation(): void
    {
        Mail::fake();

        [$user, $organization, $customer, $contact] = $this->setupCrm();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.send', $contact), [
                'email' => 'person@example.com',
                'subject' => 'First',
                'message' => 'Hi',
            ]);

        $first = CrmEmailMessage::query()->first();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('contacts.send', $contact), [
                'email' => 'person@example.com',
                'subject' => 'Re: First',
                'message' => 'Follow-up',
                'in_reply_to' => $first->rfc_message_id,
                'thread_id' => $first->thread_id,
            ]);

        $this->assertSame(1, CrmEmailConversation::query()->count());
        $this->assertSame(2, CrmEmailMessage::query()->where('conversation_id', $first->conversation_id)->count());

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('crm.communications.index'))
            ->assertOk()
            ->assertSee('First');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('crm.communications.show', $first->conversation))
            ->assertOk()
            ->assertSee('Follow-up')
            ->assertSee('person@example.com');
    }

    public function test_unsigned_webhook_is_rejected(): void
    {
        $organization = Organization::factory()->create();
        $endpoint = CrmEmailWebhookEndpoint::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'sendgrid',
            'token' => 'token-'.uniqid(),
            'signing_secret' => 'secret',
            'is_active' => true,
        ]);

        $this->postJson(route('webhooks.email', ['provider' => 'sendgrid', 'token' => $endpoint->token]), [
            ['event' => 'delivered', 'sg_event_id' => 'x', 'konnect_email_id' => 1],
        ])->assertUnauthorized();
    }
}
