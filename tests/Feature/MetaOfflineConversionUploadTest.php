<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\MarketingAttribution;
use App\Models\MarketingConversion;
use App\Models\MarketingProvider;
use App\Models\MarketingProviderSyncRun;
use App\Models\MarketingProviderUploadedConversion;
use App\Models\MarketingSession;
use App\Models\MarketingTouch;
use App\Models\MarketingVisitor;
use App\Models\Organization;
use App\Models\User;
use App\Services\Marketing\Providers\MarketingProviderRegistry;
use App\Services\MarketingProviderService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaOfflineConversionUploadTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingProviderService $providers;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'marketing.providers.meta.client_id' => 'meta-app-id',
            'marketing.providers.meta.client_secret' => 'meta-app-secret',
            'marketing.providers.meta.redirect_uri' => 'https://crm.test/marketing/providers/meta/callback',
            'marketing.providers.meta.api_version' => 'v21.0',
            'marketing.providers.meta.graph_base_url' => 'https://graph.facebook.com',
            'marketing.providers.meta.oauth_dialog_url' => 'https://www.facebook.com',
            'marketing.providers.meta.scopes' => [
                'business_management',
                'ads_read',
                'pages_show_list',
                'pages_read_engagement',
                'leads_retrieval',
            ],
            'marketing.providers.meta.timeout' => 10,
        ]);

        $this->providers = app(MarketingProviderService::class);
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
        ?string $pixelId = 'pixel_1',
        string $token = 'meta-access-token',
    ): MarketingProvider {
        $provider = $this->providers->registerProvider($organization, 'meta');

        $this->providers->storeCredentials($provider, [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_at' => now()->addDays(50),
            'external_account_id' => '100200300',
            'configuration' => [
                'business_id' => 'biz_1',
                'page_id' => 'page_1',
                'pixel_id' => $pixelId,
                'lead_form_ids' => ['form_1'],
            ],
        ]);

        return $provider->fresh(['credential']);
    }

    /**
     * @return array{0: Lead, 1: MarketingConversion}
     */
    protected function createAttributedConversion(
        Organization $organization,
        string $eventName = MarketingConversion::LEAD_CREATED,
        array $touchAttrs = ['fbclid' => 'fb.click.1'],
        ?string $email = 'jane@example.com',
    ): array {
        $visitor = MarketingVisitor::factory()->create();
        $session = MarketingSession::factory()->create([
            'visitor_id' => $visitor->id,
        ]);
        MarketingTouch::factory()->create(array_merge([
            'session_id' => $session->id,
            'occurred_at' => now()->subDay(),
            'channel' => 'paid_social',
            'source' => 'facebook',
            'medium' => 'paid_social',
        ], $touchAttrs));

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Jane Doe',
            'email' => $email,
            'phone' => '5551112222',
            'source' => 'facebook',
        ]);

        $attribution = MarketingAttribution::factory()->create([
            'organization_id' => $organization->id,
            'marketing_visitor_id' => $visitor->id,
            'marketing_session_id' => $session->id,
            'lead_id' => $lead->id,
            'is_primary' => true,
        ]);

        $conversion = MarketingConversion::factory()->create([
            'organization_id' => $organization->id,
            'marketing_attribution_id' => $attribution->id,
            'lead_id' => $lead->id,
            'event_name' => $eventName,
            'occurred_at' => now()->subHour(),
        ]);

        return [$lead, $conversion];
    }

    public function test_meta_declares_offline_conversions_capability(): void
    {
        $meta = app(MarketingProviderRegistry::class)->resolve('meta');

        $this->assertContains('offline_conversions', $meta->capabilities());
        $this->assertTrue($this->providers->supportsOfflineConversions(
            new MarketingProvider(['slug' => 'meta'])
        ));
    }

    public function test_successful_upload_posts_to_conversions_api_and_records_history(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/pixel_1/events' => Http::response([
                'events_received' => 1,
                'fbtrace_id' => 'trace-1',
            ]),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);
        [, $conversion] = $this->createAttributedConversion($organization);

        $result = $this->providers->uploadConversions($provider);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['uploaded']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(MarketingProviderSyncRun::STATUS_COMPLETED, $result['status']);

        $this->assertDatabaseHas('marketing_provider_uploaded_conversions', [
            'organization_id' => $organization->id,
            'marketing_provider_id' => $provider->id,
            'marketing_conversion_id' => $conversion->id,
            'provider_event_name' => 'Lead',
            'external_event_id' => 'nova_crm_conversion_'.$conversion->id,
        ]);

        $run = $this->providers->latestConversionUploadRun($provider);
        $this->assertNotNull($run);
        $this->assertSame(MarketingProviderSyncRun::TYPE_CONVERSION_UPLOAD, $run->sync_type);
        $this->assertSame(MarketingProviderSyncRun::DIRECTION_OUTBOUND, $run->direction);
        $this->assertSame(1, $run->records_succeeded);

        Http::assertSent(function ($request) {
            $data = $request['data'][0] ?? [];

            return str_contains($request->url(), '/pixel_1/events')
                && ($data['event_name'] ?? null) === 'Lead'
                && ($data['action_source'] ?? null) === 'system_generated'
                && isset($data['user_data']['em'][0])
                && isset($data['user_data']['fbc']);
        });
    }

    public function test_duplicate_uploads_are_skipped(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/pixel_1/events' => Http::response([
                'events_received' => 1,
                'fbtrace_id' => 'trace-1',
            ]),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);
        $this->createAttributedConversion($organization);

        $first = $this->providers->uploadConversions($provider);
        $second = $this->providers->uploadConversions($provider->fresh());

        $this->assertSame(1, $first['uploaded']);
        $this->assertSame(0, $second['uploaded']);
        $this->assertSame(1, $second['skipped']);
        $this->assertSame(1, MarketingProviderUploadedConversion::query()->count());
        $this->assertSame(2, MarketingProviderSyncRun::query()
            ->where('sync_type', MarketingProviderSyncRun::TYPE_CONVERSION_UPLOAD)
            ->count());
    }

    public function test_expired_credentials_fail_without_upload_rows(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/pixel_1/events' => Http::response([
                'error' => [
                    'message' => 'Error validating access token: Session has expired',
                    'code' => 190,
                ],
            ], 400),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);
        $this->createAttributedConversion($organization);

        $result = $this->providers->uploadConversions($provider);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $result['uploaded']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame(0, MarketingProviderUploadedConversion::query()->count());
        $this->assertSame(MarketingProvider::STATUS_EXPIRED, $provider->fresh()->status);
        $this->assertSame(MarketingProviderSyncRun::STATUS_FAILED, $result['status']);
    }

    public function test_partial_failures_continue_and_record_partial_status(): void
    {
        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;

            if ($calls === 1) {
                return Http::response([
                    'events_received' => 1,
                    'fbtrace_id' => 'ok-1',
                ]);
            }

            return Http::response([
                'error' => ['message' => 'Invalid parameter', 'code' => 100],
            ], 400);
        });

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);
        $this->createAttributedConversion($organization, MarketingConversion::LEAD_CREATED, ['fbclid' => 'fb.1'], 'one@example.com');
        $this->createAttributedConversion($organization, MarketingConversion::CUSTOMER_CREATED, ['fbclid' => 'fb.2'], 'two@example.com');

        $result = $this->providers->uploadConversions($provider);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['uploaded']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame(MarketingProviderSyncRun::STATUS_PARTIAL, $result['status']);
        $this->assertSame(1, MarketingProviderUploadedConversion::query()->count());
    }

    public function test_tenant_isolation_for_uploads(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/pixel_a/events' => Http::response(['events_received' => 1]),
            'graph.facebook.com/v21.0/pixel_b/events' => Http::response(['events_received' => 1]),
        ]);

        [, $orgA] = $this->setupUserWithOrg();
        [, $orgB] = $this->setupUserWithOrg();

        app(TenantContext::class)->set($orgA);
        $providerA = $this->connectMeta($orgA, 'pixel_a');
        $this->createAttributedConversion($orgA, MarketingConversion::LEAD_CREATED, ['fbclid' => 'a'], 'a@example.com');
        $this->providers->uploadConversions($providerA);

        app(TenantContext::class)->set($orgB);
        $providerB = $this->connectMeta($orgB, 'pixel_b');
        $this->createAttributedConversion($orgB, MarketingConversion::LEAD_CREATED, ['fbclid' => 'b'], 'b@example.com');
        $this->providers->uploadConversions($providerB);

        $this->assertSame(1, MarketingProviderUploadedConversion::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $orgA->id)
            ->count());
        $this->assertSame(1, MarketingProviderUploadedConversion::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $orgB->id)
            ->count());

        app(TenantContext::class)->set($orgA);
        $this->assertSame($providerA->id, $this->providers->latestConversionUploadRun($providerA)?->marketing_provider_id);

        app(TenantContext::class)->set($orgB);
        $this->assertSame($providerB->id, $this->providers->latestConversionUploadRun($providerB)?->marketing_provider_id);
        $this->assertNotSame(
            MarketingProviderSyncRun::query()->withoutGlobalScopes()->where('organization_id', $orgA->id)->value('id'),
            MarketingProviderSyncRun::query()->withoutGlobalScopes()->where('organization_id', $orgB->id)->value('id'),
        );
    }

    public function test_ui_upload_button_and_stats(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/pixel_1/events' => Http::response([
                'events_received' => 1,
                'fbtrace_id' => 'ui-1',
            ]),
            'graph.facebook.com/v21.0/me/businesses*' => Http::response(['data' => []]),
            'graph.facebook.com/v21.0/me/accounts*' => Http::response(['data' => []]),
        ]);

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);
        $this->createAttributedConversion($organization);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('integrations.conversions.upload', ['provider' => 'meta']))
            ->assertRedirect(route('integrations.show', ['provider' => 'meta']));

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('integrations.show', ['provider' => 'meta']))
            ->assertOk()
            ->assertSee('Offline Conversions')
            ->assertSee('Upload Conversions')
            ->assertSee('Completed');
    }

    public function test_marketing_platform_conversions_remain_immutable_source(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/pixel_1/events' => Http::response(['events_received' => 1]),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);
        [, $conversion] = $this->createAttributedConversion($organization);

        $before = MarketingConversion::query()->count();
        $this->providers->uploadConversions($provider);

        $this->assertSame($before, MarketingConversion::query()->count());
        $this->assertSame($conversion->event_name, $conversion->fresh()->event_name);
        $this->assertSame(1, MarketingProviderUploadedConversion::query()->count());
    }
}
