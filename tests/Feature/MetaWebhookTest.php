<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\MarketingProvider;
use App\Models\MarketingProviderWebhookEvent;
use App\Models\MarketingVisitor;
use App\Models\Organization;
use App\Models\User;
use App\Services\Marketing\Providers\MetaMarketingProvider;
use App\Services\MarketingProviderService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MetaWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingProviderService $providers;

    protected MetaMarketingProvider $meta;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'marketing.providers.meta.client_id' => 'meta-app-id',
            'marketing.providers.meta.client_secret' => 'meta-app-secret',
            'marketing.providers.meta.webhook_verify_token' => 'nova-verify-token',
            'marketing.providers.meta.redirect_uri' => 'https://crm.test/marketing/providers/meta/callback',
            'marketing.providers.meta.api_version' => 'v21.0',
            'marketing.providers.meta.graph_base_url' => 'https://graph.facebook.com',
            'marketing.providers.meta.oauth_dialog_url' => 'https://www.facebook.com',
            'marketing.providers.meta.scopes' => ['leads_retrieval'],
            'marketing.providers.meta.timeout' => 10,
        ]);

        $this->providers = app(MarketingProviderService::class);
        $this->meta = app(MetaMarketingProvider::class);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function connectMeta(Organization $organization): MarketingProvider
    {
        $provider = $this->providers->registerProvider($organization, 'meta');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'meta-access-token',
            'expires_at' => now()->addDays(30),
            'external_account_id' => '100200300',
            'configuration' => [
                'page_id' => 'page_1',
                'lead_form_ids' => ['form_1'],
            ],
        ]);

        return $provider->fresh(['credential']);
    }

    /**
     * @return array{payload: array<string, mixed>, raw: string, signature: string}
     */
    protected function signedLeadgenPayload(string $leadgenId = 'lead_99'): array
    {
        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'page_1',
                    'time' => 1710000000,
                    'changes' => [
                        [
                            'field' => 'leadgen',
                            'value' => [
                                'leadgen_id' => $leadgenId,
                                'page_id' => 'page_1',
                                'form_id' => 'form_1',
                                'ad_id' => 'ad_1',
                                'created_time' => 1710000000,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $raw, 'meta-app-secret');

        return compact('payload', 'raw', 'signature');
    }

    public function test_meta_declares_webhooks_capability(): void
    {
        $this->assertContains('webhooks', $this->meta->capabilities());
        $this->assertTrue($this->providers->supportsWebhooks(
            $this->providers->registerProvider(Organization::factory()->create(), 'meta')
        ));
    }

    public function test_verification_success_returns_challenge(): void
    {
        $response = $this->get('/webhooks/marketing/meta?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'nova-verify-token',
            'hub.challenge' => 'challenge-abc-123',
        ]));

        $response->assertOk();
        $this->assertSame('challenge-abc-123', $response->getContent());

        $this->assertDatabaseHas('marketing_provider_webhook_events', [
            'provider' => 'meta',
            'event_type' => MarketingProviderWebhookEvent::EVENT_VERIFICATION,
            'processing_status' => MarketingProviderWebhookEvent::STATUS_VERIFIED,
            'organization_id' => null,
        ]);
    }

    public function test_verification_failure_rejects_bad_token(): void
    {
        $response = $this->get('/webhooks/marketing/meta?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'wrong-token',
            'hub.challenge' => 'challenge-abc-123',
        ]));

        $response->assertForbidden();
        $this->assertDatabaseCount('marketing_provider_webhook_events', 0);
    }

    public function test_verification_failure_rejects_wrong_mode(): void
    {
        $response = $this->get('/webhooks/marketing/meta?'.http_build_query([
            'hub.mode' => 'unsubscribe',
            'hub.verify_token' => 'nova-verify-token',
            'hub.challenge' => 'challenge-abc-123',
        ]));

        $response->assertForbidden();
    }

    public function test_signed_webhook_is_persisted_without_crm_side_effects(): void
    {
        $visitorsBefore = MarketingVisitor::query()->count();
        $leadsBefore = Lead::query()->count();
        $signed = $this->signedLeadgenPayload();

        $response = $this->call(
            'POST',
            '/webhooks/marketing/meta',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signed['signature'],
            ],
            $signed['raw']
        );

        $response->assertOk();
        $this->assertSame('EVENT_RECEIVED', $response->getContent());

        $this->assertDatabaseCount('marketing_provider_webhook_events', 1);
        $event = MarketingProviderWebhookEvent::query()->first();
        $this->assertNotNull($event);
        $this->assertNull($event->organization_id);
        $this->assertSame('meta', $event->provider);
        $this->assertSame('leadgen', $event->event_type);
        $this->assertSame(MarketingProviderWebhookEvent::STATUS_RECEIVED, $event->processing_status);
        $this->assertNull($event->processed_at);
        $this->assertSame($signed['signature'], $event->signature);
        $this->assertSame($visitorsBefore, MarketingVisitor::query()->count());
        $this->assertSame($leadsBefore, Lead::query()->count());
        $this->assertSame(0, DB::table('marketing_conversions')->count());
        $this->assertDatabaseCount('marketing_provider_imported_leads', 0);
    }

    public function test_missing_signature_is_rejected(): void
    {
        $signed = $this->signedLeadgenPayload();

        $response = $this->call(
            'POST',
            '/webhooks/marketing/meta',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $signed['raw']
        );

        $response->assertUnauthorized();
        $this->assertDatabaseCount('marketing_provider_webhook_events', 0);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $signed = $this->signedLeadgenPayload();

        $response = $this->call(
            'POST',
            '/webhooks/marketing/meta',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.str_repeat('0', 64),
            ],
            $signed['raw']
        );

        $response->assertUnauthorized();
        $this->assertDatabaseCount('marketing_provider_webhook_events', 0);
    }

    public function test_malformed_json_payload_is_rejected(): void
    {
        $raw = '{not-json';
        $signature = 'sha256='.hash_hmac('sha256', $raw, 'meta-app-secret');

        $response = $this->call(
            'POST',
            '/webhooks/marketing/meta',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $raw
        );

        $response->assertStatus(400);
        $this->assertDatabaseCount('marketing_provider_webhook_events', 0);
    }

    public function test_malformed_meta_object_payload_is_rejected_after_signature(): void
    {
        $raw = json_encode(['hello' => 'world'], JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $raw, 'meta-app-secret');

        $response = $this->call(
            'POST',
            '/webhooks/marketing/meta',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $raw
        );

        $response->assertStatus(400);
        $this->assertDatabaseCount('marketing_provider_webhook_events', 0);
    }

    public function test_duplicate_deliveries_are_idempotent(): void
    {
        $signed = $this->signedLeadgenPayload('lead_dup');

        $first = $this->call(
            'POST',
            '/webhooks/marketing/meta',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signed['signature'],
            ],
            $signed['raw']
        );
        $first->assertOk();

        $second = $this->call(
            'POST',
            '/webhooks/marketing/meta',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signed['signature'],
            ],
            $signed['raw']
        );
        $second->assertOk();

        $this->assertDatabaseCount('marketing_provider_webhook_events', 1);
    }

    public function test_service_ingest_normalizes_leadgen_entries(): void
    {
        $signed = $this->signedLeadgenPayload('lead_norm');

        $result = $this->providers->ingestWebhook('meta', $signed['raw'], [
            'X-Hub-Signature-256' => $signed['signature'],
        ]);

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['duplicate']);
        $this->assertSame('leadgen', $result['event']);
        $this->assertSame('lead_norm', $result['normalized']['leadgen'][0]['leadgen_id'] ?? null);
        $this->assertSame(MarketingProviderWebhookEvent::STATUS_RECEIVED, $result['processing_status']);
    }

    public function test_integration_ui_shows_webhook_status(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);

        $this->providers->verifyWebhook('meta', [
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'nova-verify-token',
            'hub_challenge' => 'ui-challenge',
        ]);

        $signed = $this->signedLeadgenPayload('lead_ui');
        $this->providers->ingestWebhook('meta', $signed['raw'], [
            'X-Hub-Signature-256' => $signed['signature'],
        ]);

        $response = $this->actingAs($user)->get(route('integrations.show', ['provider' => 'meta']));
        $response->assertOk();
        $response->assertSee('Webhook Status');
        $response->assertSee('Last webhook received');
        $response->assertSee('Last verification');
        $response->assertSee('Receiving');
        $response->assertDontSee('meta-access-token');
        $response->assertDontSee('meta-app-secret');
    }

    public function test_webhook_status_helper_reports_timestamps(): void
    {
        $status = $this->providers->webhookStatus('meta');
        $this->assertTrue($status['supported']);
        $this->assertSame('awaiting', $status['status']);

        $this->providers->verifyWebhook('meta', [
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'nova-verify-token',
            'hub_challenge' => 'status-challenge',
        ]);

        $status = $this->providers->webhookStatus('meta');
        $this->assertSame('verified', $status['status']);
        $this->assertNotNull($status['last_verified_at']);

        $signed = $this->signedLeadgenPayload('lead_status');
        $this->providers->ingestWebhook('meta', $signed['raw'], [
            'X-Hub-Signature-256' => $signed['signature'],
        ]);

        $status = $this->providers->webhookStatus('meta');
        $this->assertSame('receiving', $status['status']);
        $this->assertNotNull($status['last_received_at']);
        $this->assertSame('leadgen', $status['last_event_type']);
        $this->assertSame(MarketingProviderWebhookEvent::STATUS_RECEIVED, $status['last_processing_status']);
    }

    public function test_unknown_provider_webhook_returns_not_found(): void
    {
        $response = $this->get('/webhooks/marketing/google_ads?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'nova-verify-token',
            'hub.challenge' => 'x',
        ]));

        $response->assertNotFound();
    }
}
