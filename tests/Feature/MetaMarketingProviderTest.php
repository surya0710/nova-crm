<?php

namespace Tests\Feature;

use App\Models\MarketingProvider;
use App\Models\MarketingProviderCredential;
use App\Models\MarketingVisitor;
use App\Models\Organization;
use App\Models\User;
use App\Services\Marketing\Providers\MarketingProviderRegistry;
use App\Services\Marketing\Providers\MetaMarketingProvider;
use App\Services\MarketingProviderService;
use App\Services\MarketingTrackingService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaMarketingProviderTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingProviderRegistry $registry;

    protected MarketingProviderService $providers;

    protected MetaMarketingProvider $meta;

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
            'marketing.providers.meta.scopes' => ['business_management', 'ads_read'],
            'marketing.providers.meta.timeout' => 10,
        ]);

        $this->registry = app(MarketingProviderRegistry::class);
        $this->providers = app(MarketingProviderService::class);
        $this->meta = $this->registry->resolve('meta');
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

    protected function registerMetaConnection(Organization $organization): MarketingProvider
    {
        return $this->providers->registerProvider($organization, 'meta');
    }

    public function test_meta_is_registered_in_provider_registry(): void
    {
        $this->assertTrue($this->registry->has('meta'));
        $this->assertInstanceOf(MetaMarketingProvider::class, $this->meta);
        $this->assertSame('meta', $this->meta->slug());
        $this->assertSame(['oauth', 'asset_discovery', 'lead_form_sync', 'lead_import', 'webhooks', 'offline_conversions'], $this->meta->capabilities());
        $this->assertSame('Meta Business', $this->meta->displayName());
    }

    public function test_authorize_start_generates_oauth_url_with_state(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = $this->registerMetaConnection($organization);
        $result = $this->providers->authorize($provider, ['phase' => 'start']);

        $this->assertArrayHasKey('authorization_url', $result);
        $this->assertStringContainsString('https://www.facebook.com/v21.0/dialog/oauth?', $result['authorization_url']);
        $this->assertStringContainsString('client_id=meta-app-id', $result['authorization_url']);
        $this->assertStringContainsString('response_type=code', $result['authorization_url']);
        $this->assertStringContainsString('business_management', $result['authorization_url']);
        $this->assertNotEmpty($result['metadata']['state']);

        $state = json_decode(Crypt::decryptString($result['metadata']['state']), true);
        $this->assertSame($provider->id, $state['provider_id']);
        $this->assertSame($organization->id, $state['organization_id']);
        $this->assertSame('meta', $state['slug']);
    }

    public function test_oauth_callback_stores_encrypted_long_lived_credentials(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/oauth/access_token*' => Http::sequence()
                ->push(['access_token' => 'short-lived-token', 'token_type' => 'bearer', 'expires_in' => 3600])
                ->push(['access_token' => 'long-lived-token', 'token_type' => 'bearer', 'expires_in' => 5184000]),
            'graph.facebook.com/v21.0/me*' => Http::response([
                'id' => '100200300',
                'name' => 'Meta Test User',
            ]),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = $this->registerMetaConnection($organization);
        $start = $this->providers->authorize($provider, ['phase' => 'start']);

        $this->providers->authorize($provider->fresh(), [
            'phase' => 'callback',
            'code' => 'auth-code-123',
            'state' => $start['metadata']['state'],
        ]);

        $provider->refresh();
        $this->assertSame(MarketingProvider::STATUS_CONNECTED, $provider->status);
        $this->assertSame('100200300', $provider->external_account_id);
        $this->assertNotNull($provider->connected_at);
        $this->assertSame('long-lived-token', $provider->credential->access_token);
        $this->assertNull($provider->credential->refresh_token);
        $this->assertTrue($provider->credential->expires_at->greaterThan(now()->addDays(50)));

        $row = DB::table('marketing_provider_credentials')->where('marketing_provider_id', $provider->id)->first();
        $this->assertNotSame('long-lived-token', $row->access_token);
        $this->assertStringNotContainsString('long-lived-token', (string) $row->access_token);
    }

    public function test_http_oauth_connect_redirects_to_meta(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('marketing.providers.connect', ['provider' => 'meta']));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('facebook.com', $location);
        $this->assertStringContainsString('client_id=meta-app-id', $location);

        $this->assertDatabaseHas('marketing_providers', [
            'organization_id' => $organization->id,
            'slug' => 'meta',
            'status' => MarketingProvider::STATUS_DISCONNECTED,
        ]);
    }

    public function test_http_oauth_callback_completes_connection(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/oauth/access_token*' => Http::sequence()
                ->push(['access_token' => 'short-token', 'expires_in' => 3600])
                ->push(['access_token' => 'long-token', 'expires_in' => 5184000]),
            'graph.facebook.com/v21.0/me*' => Http::response(['id' => '99', 'name' => 'Callback User']),
        ]);

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = $this->registerMetaConnection($organization);
        $start = $this->meta->authorize($provider, ['phase' => 'start']);
        $state = $start['metadata']['state'];

        $response = $this->actingAs($user)
            ->withSession([
                'current_organization_id' => $organization->id,
                'marketing_oauth_state_meta' => $state,
            ])
            ->get(route('marketing.providers.callback', [
                'provider' => 'meta',
                'code' => 'http-code',
                'state' => $state,
            ]));

        $response->assertRedirect(route('integrations.index'));
        $this->assertSame(MarketingProvider::STATUS_CONNECTED, $provider->fresh()->status);
        $this->assertSame('long-token', $provider->fresh()->credential->access_token);
    }

    public function test_token_refresh_exchanges_long_lived_token(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/oauth/access_token*' => Http::response([
                'access_token' => 'refreshed-long-token',
                'token_type' => 'bearer',
                'expires_in' => 5184000,
            ]),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = $this->registerMetaConnection($organization);
        $this->providers->storeCredentials($provider, [
            'access_token' => 'old-long-token',
            'expires_at' => now()->addDays(5),
            'scopes' => ['ads_read'],
        ]);

        $this->providers->refreshCredentials($provider->fresh());

        $this->assertSame('refreshed-long-token', $provider->fresh()->credential->access_token);
        $this->assertSame(MarketingProvider::STATUS_CONNECTED, $provider->fresh()->status);
    }

    public function test_disconnect_clears_credentials_and_revokes_remotely(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/me/permissions*' => Http::response(['success' => true]),
        ]);

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = $this->registerMetaConnection($organization);
        $this->providers->storeCredentials($provider, [
            'access_token' => 'token-to-revoke',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('marketing.providers.disconnect', ['provider' => 'meta']));

        $response->assertRedirect(route('integrations.index'));
        $this->assertSame(MarketingProvider::STATUS_DISCONNECTED, $provider->fresh()->status);
        $this->assertNull($provider->fresh()->credential);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/me/permissions'));
    }

    public function test_reconnect_preserves_provider_row_and_replaces_credentials(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/oauth/access_token*' => Http::sequence()
                ->push(['access_token' => 'short-a', 'expires_in' => 3600])
                ->push(['access_token' => 'long-a', 'expires_in' => 5184000])
                ->push(['access_token' => 'short-b', 'expires_in' => 3600])
                ->push(['access_token' => 'long-b', 'expires_in' => 5184000]),
            'graph.facebook.com/v21.0/me*' => Http::sequence()
                ->push(['id' => '1', 'name' => 'User A'])
                ->push(['id' => '1', 'name' => 'User A']),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = $this->registerMetaConnection($organization);
        $startA = $this->providers->authorize($provider, ['phase' => 'start']);
        $this->providers->authorize($provider->fresh(), [
            'phase' => 'callback',
            'code' => 'code-a',
            'state' => $startA['metadata']['state'],
        ]);

        $providerId = $provider->id;
        $this->assertSame('long-a', $provider->fresh()->credential->access_token);

        $startB = $this->providers->authorize($provider->fresh(), ['phase' => 'start']);
        $this->assertSame(MarketingProvider::STATUS_CONNECTED, $provider->fresh()->status);

        $this->providers->authorize($provider->fresh(), [
            'phase' => 'callback',
            'code' => 'code-b',
            'state' => $startB['metadata']['state'],
        ]);

        $this->assertSame($providerId, $provider->fresh()->id);
        $this->assertSame('long-b', $provider->fresh()->credential->access_token);
        $this->assertSame(1, MarketingProvider::query()->where('organization_id', $organization->id)->where('slug', 'meta')->count());
    }

    public function test_health_check_connected_and_expired_paths(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/me*' => Http::response(['id' => '55', 'name' => 'Healthy']),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = $this->registerMetaConnection($organization);
        $this->providers->storeCredentials($provider, [
            'access_token' => 'healthy-token',
            'expires_at' => now()->addDays(30),
        ]);

        $ok = $this->providers->checkHealth($provider->fresh());
        $this->assertTrue($ok['healthy']);
        $this->assertSame(MarketingProvider::STATUS_CONNECTED, $ok['status']);
        $this->assertSame('55', $ok['metadata']['meta_user_id']);

        $this->providers->storeCredentials($provider->fresh(), [
            'access_token' => 'expired-token',
            'expires_at' => now()->subMinute(),
        ]);

        $expired = $this->providers->checkHealth($provider->fresh());
        $this->assertFalse($expired['healthy']);
        $this->assertSame(MarketingProvider::STATUS_EXPIRED, $provider->fresh()->status);
    }

    public function test_health_check_marks_error_on_api_failure(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/me*' => Http::response([
                'error' => ['message' => 'Unsupported get request', 'code' => 100],
            ], 400),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = $this->registerMetaConnection($organization);
        $this->providers->storeCredentials($provider, [
            'access_token' => 'bad-token',
            'expires_at' => now()->addDay(),
        ]);

        $result = $this->providers->checkHealth($provider->fresh());
        $this->assertFalse($result['healthy']);
        $this->assertSame(MarketingProvider::STATUS_ERROR, $provider->fresh()->status);
    }

    public function test_out_of_scope_capabilities_return_unsupported(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->registerMetaConnection($organization);

        $this->assertFalse($this->meta->synchronize($provider)['ok']);
        $this->assertStringContainsString('Not yet implemented', $this->meta->synchronize($provider)['message']);

        $this->providers->storeCredentials($provider, [
            'access_token' => 'token',
            'expires_at' => now()->addDay(),
            'configuration' => [],
        ]);

        // Offline conversions require a configured pixel and usable identifiers.
        $upload = $this->meta->uploadConversions($provider->fresh(['credential']), [
            ['event_name' => 'lead_created', 'email' => 'a@example.com'],
        ]);
        $this->assertFalse($upload['ok']);
        $this->assertSame(1, $upload['failed']);
        $this->assertStringContainsString('pixel_id', (string) $upload['message']);
    }

    public function test_meta_connections_are_tenant_isolated(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/oauth/access_token*' => Http::sequence()
                ->push(['access_token' => 'short-a', 'expires_in' => 3600])
                ->push(['access_token' => 'long-a', 'expires_in' => 5184000]),
            'graph.facebook.com/v21.0/me*' => Http::response(['id' => 'org-a-user', 'name' => 'A']),
        ]);

        [, $orgA] = $this->setupUserWithOrg();
        [, $orgB] = $this->setupUserWithOrg();

        app(TenantContext::class)->set($orgA);
        $providerA = $this->registerMetaConnection($orgA);
        $start = $this->providers->authorize($providerA, ['phase' => 'start']);
        $this->providers->authorize($providerA->fresh(), [
            'phase' => 'callback',
            'code' => 'code-a',
            'state' => $start['metadata']['state'],
        ]);

        app(TenantContext::class)->set($orgB);
        $providerB = $this->registerMetaConnection($orgB);

        $this->assertNotSame($providerA->id, $providerB->id);
        $this->assertNull($this->providers->findProviderForOrganization($orgB, $providerA->id));

        app(TenantContext::class)->set($orgA);
        $this->assertSame('long-a', $this->providers->findProvider($orgA, 'meta')?->credential?->access_token);

        app(TenantContext::class)->set($orgB);
        $this->assertNull($this->providers->findProvider($orgB, 'meta')?->credential);
    }

    public function test_invalid_oauth_state_is_rejected(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = $this->registerMetaConnection($organization);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Meta OAuth state is invalid');

        $this->providers->authorize($provider, [
            'phase' => 'callback',
            'code' => 'code',
            'state' => 'tampered-state',
        ]);
    }

    public function test_marketing_platform_runtime_unaffected_by_meta_provider(): void
    {
        $beforeVisitors = MarketingVisitor::query()->count();
        $beforeCredentials = MarketingProviderCredential::query()->count();

        $tracking = app(MarketingTrackingService::class);
        $visitor = $tracking->createVisitor(['ip' => '203.0.113.91']);
        $session = $tracking->createSession($visitor);
        $tracking->recordPageView($session, [
            'url' => 'https://example.test/meta-foundation',
            'referrer' => null,
        ]);

        $this->assertSame($beforeVisitors + 1, MarketingVisitor::query()->count());
        $this->assertSame($beforeCredentials, MarketingProviderCredential::query()->count());
    }
}
