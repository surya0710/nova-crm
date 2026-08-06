<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\MarketingProvider;
use App\Models\MarketingProviderWebhookEvent;
use App\Models\MarketingVisitor;
use App\Models\Organization;
use App\Models\User;
use App\Services\Marketing\Providers\MetaWebhookProcessor;
use App\Services\MarketingProviderService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaWebhookProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingProviderService $providers;

    protected MetaWebhookProcessor $processor;

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
        $this->processor = app(MetaWebhookProcessor::class);
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

    protected function connectMeta(
        Organization $organization,
        string $pageId = 'page_1',
        array $formIds = ['form_1'],
        string $token = 'meta-access-token',
    ): MarketingProvider {
        $provider = $this->providers->registerProvider($organization, 'meta');
        $this->providers->storeCredentials($provider, [
            'access_token' => $token,
            'expires_at' => now()->addDays(40),
            'external_account_id' => '100200300',
            'configuration' => [
                'page_id' => $pageId,
                'lead_form_ids' => $formIds,
            ],
        ]);

        return $provider->fresh(['credential']);
    }

    /**
     * Ingest a signed leadgen webhook the same way Meta would (via the public
     * ingest path) so the stored event carries the normalized structure.
     */
    protected function ingestLeadgen(
        string $leadgenId = 'lead_1',
        string $pageId = 'page_1',
        string $formId = 'form_1',
        ?int $time = null,
    ): MarketingProviderWebhookEvent {
        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => $pageId,
                    'time' => $time ?? 1710000000,
                    'changes' => [
                        [
                            'field' => 'leadgen',
                            'value' => [
                                'leadgen_id' => $leadgenId,
                                'page_id' => $pageId,
                                'form_id' => $formId,
                                'ad_id' => 'ad_1',
                                'created_time' => $time ?? 1710000000,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $raw, 'meta-app-secret');

        $result = $this->providers->ingestWebhook('meta', $raw, [
            'X-Hub-Signature-256' => $signature,
        ]);

        return MarketingProviderWebhookEvent::query()->findOrFail($result['webhook_event_id']);
    }

    protected function fakeLead(string $leadId = 'lead_1', ?array $fieldData = null): void
    {
        $fieldData ??= [
            ['name' => 'full_name', 'values' => ['Grace Hopper']],
            ['name' => 'email', 'values' => ['grace@example.com']],
            ['name' => 'phone_number', 'values' => ['5559990000']],
            ['name' => 'city', 'values' => ['Boston']],
        ];

        Http::fake([
            'graph.facebook.com/*/'.$leadId.'*' => Http::response([
                'id' => $leadId,
                'created_time' => '2026-07-12T10:00:00+0000',
                'ad_id' => 'ad_1',
                'ad_name' => 'Spring Ad',
                'form_id' => 'form_1',
                'field_data' => $fieldData,
            ]),
        ]);
    }

    public function test_successful_webhook_processing_creates_lead(): void
    {
        $this->fakeLead('lead_1');
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);

        $event = $this->ingestLeadgen('lead_1');
        $visitorsBefore = MarketingVisitor::query()->count();

        $result = $this->processor->process($event);

        $this->assertTrue($result['ok']);
        $this->assertSame(MarketingProviderWebhookEvent::STATUS_PROCESSED, $result['status']);
        $this->assertSame(1, $result['imported']);
        $this->assertSame($organization->id, $result['organization_id']);

        $lead = Lead::query()->where('email', 'grace@example.com')->first();
        $this->assertNotNull($lead);
        $this->assertSame('Grace Hopper', $lead->name);
        $this->assertSame('5559990000', $lead->phone);
        $this->assertSame('facebook', $lead->source);
        $this->assertSame($organization->id, $lead->organization_id);
        $this->assertSame('Boston', $lead->custom_fields['provider']['unmapped_fields']['city'] ?? null);
        $this->assertSame('lead_1', $lead->custom_fields['provider']['external_lead_id'] ?? null);

        $event->refresh();
        $this->assertSame(MarketingProviderWebhookEvent::STATUS_PROCESSED, $event->processing_status);
        $this->assertNotNull($event->processed_at);
        $this->assertSame($organization->id, $event->organization_id);
        $this->assertSame(1, $event->processing_attempts);

        $this->assertDatabaseCount('marketing_provider_imported_leads', 1);
        // No visitor context → no attribution → no conversion writes.
        $this->assertSame($visitorsBefore, MarketingVisitor::query()->count());
        $this->assertSame(0, DB::table('marketing_conversions')->count());
    }

    public function test_lead_details_come_from_graph_not_webhook_payload(): void
    {
        // Webhook only carries the leadgen_id; all lead fields must be fetched.
        $this->fakeLead('lead_graph', [
            ['name' => 'email', 'values' => ['fetched@example.com']],
            ['name' => 'full_name', 'values' => ['Fetched Person']],
        ]);
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);

        $event = $this->ingestLeadgen('lead_graph');
        $this->processor->process($event);

        $lead = Lead::query()->where('email', 'fetched@example.com')->first();
        $this->assertNotNull($lead);
        $this->assertSame('Fetched Person', $lead->name);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/lead_graph'));
    }

    public function test_organization_resolution_is_tenant_isolated_by_form(): void
    {
        $this->fakeLead('lead_b');

        [, $orgA] = $this->setupUserWithOrg();
        [, $orgB] = $this->setupUserWithOrg();

        app(TenantContext::class)->set($orgA);
        $this->connectMeta($orgA, 'page_a', ['form_a']);
        app(TenantContext::class)->set($orgB);
        $this->connectMeta($orgB, 'page_b', ['form_b']);

        // Clear ambient context to mimic an unauthenticated webhook.
        app(TenantContext::class)->set(null);

        $event = $this->ingestLeadgen('lead_b', 'page_b', 'form_b');
        $result = $this->processor->process($event);

        $this->assertTrue($result['ok']);
        $this->assertSame($orgB->id, $result['organization_id']);

        $lead = Lead::query()->withoutGlobalScopes()->where('email', 'grace@example.com')->first();
        $this->assertNotNull($lead);
        $this->assertSame($orgB->id, $lead->organization_id);
        $this->assertSame(0, Lead::query()->withoutGlobalScopes()->where('organization_id', $orgA->id)->count());
    }

    public function test_duplicate_delivery_is_stored_once_and_processed_once(): void
    {
        $this->fakeLead('lead_dup');
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);

        $first = $this->ingestLeadgen('lead_dup', time: 1710000000);
        $second = $this->ingestLeadgen('lead_dup', time: 1710000000);

        // Identical raw body → deduped at ingest to one stored event.
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('marketing_provider_webhook_events', 1);

        $this->processor->processPending('meta');

        $this->assertSame(1, Lead::query()->count());
    }

    public function test_duplicate_lead_is_prevented_across_distinct_events(): void
    {
        $this->fakeLead('lead_same');
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);

        // Two distinct deliveries (different times → different bodies) but the
        // same leadgen_id must create only one CRM lead.
        $eventA = $this->ingestLeadgen('lead_same', time: 1710000001);
        $eventB = $this->ingestLeadgen('lead_same', time: 1710000002);
        $this->assertNotSame($eventA->id, $eventB->id);

        $resultA = $this->processor->process($eventA);
        $resultB = $this->processor->process($eventB);

        $this->assertSame(1, $resultA['imported']);
        $this->assertSame(0, $resultB['imported']);
        $this->assertSame(1, $resultB['skipped']);
        $this->assertSame(1, Lead::query()->count());
        $this->assertDatabaseCount('marketing_provider_imported_leads', 1);
    }

    public function test_manual_import_and_webhook_share_dedup_pipeline(): void
    {
        // Manually import a lead, then a webhook for the same lead must skip.
        Http::fake([
            'graph.facebook.com/*/form_1/leads*' => Http::response([
                'data' => [[
                    'id' => 'lead_shared',
                    'created_time' => '2026-07-12T10:00:00+0000',
                    'form_id' => 'form_1',
                    'field_data' => [
                        ['name' => 'full_name', 'values' => ['Shared Person']],
                        ['name' => 'email', 'values' => ['shared@example.com']],
                    ],
                ]],
            ]),
            'graph.facebook.com/*/lead_shared*' => Http::response([
                'id' => 'lead_shared',
                'form_id' => 'form_1',
                'field_data' => [
                    ['name' => 'full_name', 'values' => ['Shared Person']],
                    ['name' => 'email', 'values' => ['shared@example.com']],
                ],
            ]),
        ]);

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);

        $manual = $this->providers->importLeadEntries($provider, $user);
        $this->assertSame(1, $manual['imported']);
        $this->assertSame(1, Lead::query()->count());

        $event = $this->ingestLeadgen('lead_shared');
        $result = $this->processor->process($event);

        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, Lead::query()->count());
    }

    public function test_unknown_organization_marks_event_failed(): void
    {
        $this->fakeLead('lead_orphan');
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization, 'page_1', ['form_1']);
        app(TenantContext::class)->set(null);

        // form_x / page_x belong to no connected organization.
        $event = $this->ingestLeadgen('lead_orphan', 'page_x', 'form_x');
        $result = $this->processor->process($event);

        $this->assertFalse($result['ok']);
        $this->assertSame(MarketingProviderWebhookEvent::STATUS_FAILED, $result['status']);
        $this->assertSame(0, Lead::query()->withoutGlobalScopes()->count());

        $event->refresh();
        $this->assertSame(MarketingProviderWebhookEvent::STATUS_FAILED, $event->processing_status);
        $this->assertStringContainsString('no_organization', (string) $event->failure_reason);
        $this->assertNotNull($event->processed_at);
    }

    public function test_revoked_permissions_marks_failed_and_updates_provider(): void
    {
        Http::fake([
            'graph.facebook.com/*/lead_revoked*' => Http::response([
                'error' => ['message' => 'Permissions error (#10) Application does not have permission', 'code' => 10],
            ], 403),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);

        $event = $this->ingestLeadgen('lead_revoked');
        $result = $this->processor->process($event);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, Lead::query()->count());
        $event->refresh();
        $this->assertSame(MarketingProviderWebhookEvent::STATUS_FAILED, $event->processing_status);
        $this->assertSame(MarketingProvider::STATUS_ERROR, $provider->fresh()->status);
    }

    public function test_expired_credentials_marks_failed_and_expires_provider(): void
    {
        Http::fake([
            'graph.facebook.com/*/lead_expired*' => Http::response([
                'error' => ['message' => 'Error validating access token: Session has expired (#190)', 'code' => 190],
            ], 401),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);

        $event = $this->ingestLeadgen('lead_expired');
        $result = $this->processor->process($event);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, Lead::query()->count());
        $this->assertSame(MarketingProvider::STATUS_EXPIRED, $provider->fresh()->status);
    }

    public function test_deleted_lead_or_form_marks_failed(): void
    {
        Http::fake([
            'graph.facebook.com/*/lead_missing*' => Http::response([
                'error' => ['message' => 'Unsupported get request. Object does not exist (#100)', 'code' => 100],
            ], 400),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);

        $event = $this->ingestLeadgen('lead_missing');
        $result = $this->processor->process($event);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, Lead::query()->count());
        $event->refresh();
        $this->assertSame(MarketingProviderWebhookEvent::STATUS_FAILED, $event->processing_status);
    }

    public function test_graph_api_failure_marks_failed_without_crashing(): void
    {
        Http::fake([
            'graph.facebook.com/*/lead_boom*' => Http::response([
                'error' => ['message' => 'Temporary server error', 'code' => 2],
            ], 500),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);

        $event = $this->ingestLeadgen('lead_boom');
        $result = $this->processor->process($event);

        $this->assertFalse($result['ok']);
        $this->assertSame(MarketingProviderWebhookEvent::STATUS_FAILED, $result['status']);
        $this->assertSame(0, Lead::query()->count());
    }

    public function test_non_leadgen_event_is_ignored(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);

        $payload = [
            'object' => 'page',
            'entry' => [[
                'id' => 'page_1',
                'time' => 1710000000,
                'changes' => [['field' => 'feed', 'value' => ['item' => 'status']]],
            ]],
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $raw, 'meta-app-secret');
        $ingest = $this->providers->ingestWebhook('meta', $raw, ['X-Hub-Signature-256' => $signature]);
        $event = MarketingProviderWebhookEvent::query()->findOrFail($ingest['webhook_event_id']);

        $result = $this->processor->process($event);

        $this->assertTrue($result['ok']);
        $this->assertSame(MarketingProviderWebhookEvent::STATUS_IGNORED, $result['status']);
        $this->assertSame(0, Lead::query()->count());
        $event->refresh();
        $this->assertSame(MarketingProviderWebhookEvent::STATUS_IGNORED, $event->processing_status);
        $this->assertNotNull($event->processed_at);
    }

    public function test_partial_failures_do_not_block_other_events(): void
    {
        $this->fakeLead('lead_ok');
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);
        app(TenantContext::class)->set(null);

        $this->ingestLeadgen('lead_ok', 'page_1', 'form_1', time: 1710000001);
        $this->ingestLeadgen('lead_orphan', 'page_x', 'form_x', time: 1710000002);

        $summary = $this->processor->processPending('meta');

        $this->assertSame(2, $summary['events']);
        $this->assertSame(1, $summary['processed']);
        $this->assertSame(1, $summary['failed']);
        $this->assertSame(1, $summary['imported']);
        $this->assertSame(1, Lead::query()->withoutGlobalScopes()->count());

        // Both events reached a terminal state.
        $this->assertSame(0, MarketingProviderWebhookEvent::query()
            ->where('processing_status', MarketingProviderWebhookEvent::STATUS_RECEIVED)
            ->where('event_type', '!=', MarketingProviderWebhookEvent::EVENT_VERIFICATION)
            ->count());
    }

    public function test_processing_is_idempotent_for_finalized_events(): void
    {
        $this->fakeLead('lead_once');
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);

        $event = $this->ingestLeadgen('lead_once');
        $this->processor->process($event);
        $this->assertSame(1, Lead::query()->count());

        $again = $this->processor->process($event->fresh());
        $this->assertSame(0, $again['imported']);
        $this->assertSame(1, Lead::query()->count());
    }

    public function test_webhook_status_reports_processing_stats(): void
    {
        $this->fakeLead('lead_stats');
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);

        $event = $this->ingestLeadgen('lead_stats');
        $this->processor->process($event);

        $status = $this->providers->webhookStatus('meta');
        $this->assertSame(1, $status['processed_count']);
        $this->assertSame(0, $status['failed_count']);
        $this->assertSame('processed', $status['last_processing_result']);
        $this->assertNotNull($status['last_processed_at']);
    }

    public function test_ui_process_button_triggers_processing(): void
    {
        $this->fakeLead('lead_ui');
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);
        $this->ingestLeadgen('lead_ui');

        $response = $this->actingAs($user)
            ->post(route('integrations.webhooks.process', ['provider' => 'meta']));

        $response->assertRedirect(route('integrations.show', ['provider' => 'meta']));
        $this->assertSame(1, Lead::query()->count());
    }

    public function test_resolution_helper_reports_reasons(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization, 'page_1', ['form_1']);
        app(TenantContext::class)->set(null);

        $resolved = $this->providers->resolveWebhookProvider('meta', 'page_1', 'form_1');
        $this->assertNotNull($resolved['provider']);
        $this->assertSame($organization->id, $resolved['provider']->organization_id);

        $unknown = $this->providers->resolveWebhookProvider('meta', 'page_x', 'form_x');
        $this->assertNull($unknown['provider']);
        $this->assertSame('no_organization', $unknown['reason']);
    }
}
