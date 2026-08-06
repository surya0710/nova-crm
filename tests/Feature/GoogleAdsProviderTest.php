<?php

namespace Tests\Feature;

use App\Models\MarketingProvider;
use App\Models\Organization;
use App\Models\User;
use App\Services\Marketing\Providers\GoogleAdsProvider;
use App\Services\Marketing\Providers\MarketingProviderRegistry;
use App\Services\MarketingProviderService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GoogleAdsProviderTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingProviderRegistry $registry;

    protected MarketingProviderService $providers;

    protected GoogleAdsProvider $google;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'marketing.providers.google_ads.client_id' => 'google-client-id',
            'marketing.providers.google_ads.client_secret' => 'google-client-secret',
            'marketing.providers.google_ads.redirect_uri' => 'https://crm.test/marketing/providers/google_ads/callback',
            'marketing.providers.google_ads.developer_token' => 'google-developer-token',
            'marketing.providers.google_ads.api_version' => 'v22',
            'marketing.providers.google_ads.timeout' => 10,
            'marketing.providers.google_ads.authorization_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'marketing.providers.google_ads.token_url' => 'https://oauth2.googleapis.com/token',
            'marketing.providers.google_ads.token_info_url' => 'https://oauth2.googleapis.com/tokeninfo',
            'marketing.providers.google_ads.revoke_url' => 'https://oauth2.googleapis.com/revoke',
            'marketing.providers.google_ads.api_base_url' => 'https://googleads.googleapis.com',
            'marketing.providers.google_ads.scopes' => [
                'https://www.googleapis.com/auth/adwords',
                'openid',
                'email',
            ],
        ]);

        $this->registry = app(MarketingProviderRegistry::class);
        $this->providers = app(MarketingProviderService::class);
        $this->google = $this->registry->resolve('google_ads');
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

    protected function registerConnection(Organization $organization): MarketingProvider
    {
        return $this->providers->registerProvider($organization, 'google_ads');
    }

    public function test_google_ads_is_registered_with_expected_capabilities(): void
    {
        $this->assertTrue($this->registry->has('google_ads'));
        $this->assertInstanceOf(GoogleAdsProvider::class, $this->google);
        $this->assertSame('google_ads', $this->google->slug());
        $this->assertSame('Google Ads', $this->google->displayName());
        $this->assertSame(
            ['oauth', 'token_refresh', 'asset_discovery', 'offline_conversions'],
            $this->google->capabilities(),
        );
    }

    public function test_authorization_url_requests_offline_consent_and_tenant_bound_state(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = $this->registerConnection($organization);
        $result = $this->providers->authorize($provider, ['phase' => 'start']);

        $query = [];
        parse_str((string) parse_url($result['authorization_url'], PHP_URL_QUERY), $query);

        $this->assertSame('google-client-id', $query['client_id']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('offline', $query['access_type']);
        $this->assertSame('consent', $query['prompt']);
        $this->assertSame('true', $query['include_granted_scopes']);
        $this->assertStringContainsString('https://www.googleapis.com/auth/adwords', $query['scope']);

        $state = json_decode(Crypt::decryptString($result['metadata']['state']), true);
        $this->assertSame($provider->id, $state['provider_id']);
        $this->assertSame($organization->id, $state['organization_id']);
        $this->assertSame('google_ads', $state['slug']);
    }

    public function test_successful_callback_stores_encrypted_tokens_and_nullable_customer_configuration(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
                'refresh_token' => 'google-refresh-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
                'scope' => 'https://www.googleapis.com/auth/adwords openid email',
            ]),
            'oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-user-123',
                'email' => 'owner@example.test',
                'expires_in' => '3599',
            ]),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = $this->registerConnection($organization);
        $start = $this->providers->authorize($provider, ['phase' => 'start']);
        $this->providers->authorize($provider->fresh(), [
            'phase' => 'callback',
            'code' => 'authorization-code',
            'state' => $start['metadata']['state'],
        ]);

        $connected = $provider->fresh(['credential']);
        $this->assertSame(MarketingProvider::STATUS_CONNECTED, $connected->status);
        $this->assertSame('google-user-123', $connected->external_account_id);
        $this->assertSame('google-access-token', $connected->credential->access_token);
        $this->assertSame('google-refresh-token', $connected->credential->refresh_token);
        $this->assertArrayHasKey('customer_id', $connected->credential->configuration);
        $this->assertNull($connected->credential->configuration['customer_id']);
        $this->assertSame('owner@example.test', $connected->credential->metadata['google_email']);

        $row = DB::table('marketing_provider_credentials')
            ->where('marketing_provider_id', $provider->id)
            ->first();
        $this->assertNotSame('google-access-token', $row->access_token);
        $this->assertNotSame('google-refresh-token', $row->refresh_token);
        $this->assertStringNotContainsString('google-access-token', (string) $row->access_token);
        $this->assertStringNotContainsString('google-refresh-token', (string) $row->refresh_token);
    }

    public function test_http_connect_and_callback_use_generic_provider_routes(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'http-access',
                'refresh_token' => 'http-refresh',
                'expires_in' => 3600,
            ]),
            'oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'http-google-user',
                'email' => 'http@example.test',
            ]),
        ]);

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $connect = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('marketing.providers.connect', ['provider' => 'google_ads']));

        $connect->assertRedirect();
        $this->assertStringContainsString(
            'accounts.google.com/o/oauth2/v2/auth',
            (string) $connect->headers->get('Location'),
        );

        $provider = $this->providers->findProvider($organization, 'google_ads');
        $start = $this->google->authorize($provider, ['phase' => 'start']);
        $state = $start['metadata']['state'];

        $callback = $this->actingAs($user)
            ->withSession([
                'current_organization_id' => $organization->id,
                'marketing_oauth_state_google_ads' => $state,
            ])
            ->get(route('marketing.providers.callback', [
                'provider' => 'google_ads',
                'code' => 'http-code',
                'state' => $state,
            ]));

        $callback->assertRedirect(route('integrations.index'));
        $callback->assertSessionHas('status', 'integration-connected');
        $this->assertSame(MarketingProvider::STATUS_CONNECTED, $provider->fresh()->status);
        $this->assertSame('http-access', $provider->fresh()->credential->access_token);
    }

    public function test_denied_consent_and_invalid_session_state_are_rejected(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->registerConnection($organization);

        $denied = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('marketing.providers.callback', [
                'provider' => 'google_ads',
                'error' => 'access_denied',
                'error_description' => 'The user denied access.',
            ]));

        $denied->assertRedirect(route('integrations.index'));
        $denied->assertSessionHas('error');
        $this->assertSame(MarketingProvider::STATUS_DISCONNECTED, $provider->fresh()->status);

        $invalid = $this->actingAs($user)
            ->withSession([
                'current_organization_id' => $organization->id,
                'marketing_oauth_state_google_ads' => 'expected-state',
            ])
            ->get(route('marketing.providers.callback', [
                'provider' => 'google_ads',
                'code' => 'code',
                'state' => 'wrong-state',
            ]));

        $invalid->assertRedirect(route('integrations.index'));
        $invalid->assertSessionHas('error', 'Invalid OAuth state.');
        Http::assertNothingSent();
    }

    public function test_tampered_adapter_state_is_rejected_before_token_exchange(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Google Ads OAuth state is invalid');

        $this->providers->authorize($this->registerConnection($organization), [
            'phase' => 'callback',
            'code' => 'code',
            'state' => 'tampered-state',
        ]);
    }

    public function test_invalid_redirect_token_exchange_is_normalized_and_marks_http_connection_error(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'error' => 'redirect_uri_mismatch',
                'error_description' => 'Bad Request',
            ], 400),
        ]);

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->registerConnection($organization);
        $start = $this->google->authorize($provider, ['phase' => 'start']);
        $state = $start['metadata']['state'];

        $response = $this->actingAs($user)
            ->withSession([
                'current_organization_id' => $organization->id,
                'marketing_oauth_state_google_ads' => $state,
            ])
            ->get(route('marketing.providers.callback', [
                'provider' => 'google_ads',
                'code' => 'bad-code',
                'state' => $state,
            ]));

        $response->assertRedirect(route('integrations.index'));
        $response->assertSessionHas('error');
        $this->assertSame(MarketingProvider::STATUS_ERROR, $provider->fresh()->status);
        $this->assertStringContainsString('redirect_uri_mismatch', (string) $provider->fresh()->last_error);
    }

    public function test_token_refresh_preserves_refresh_token_and_configuration(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'refreshed-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->registerConnection($organization);
        $this->providers->storeCredentials($provider, [
            'access_token' => 'old-access',
            'refresh_token' => 'durable-refresh',
            'expires_at' => now()->addMinutes(5),
            'scopes' => ['https://www.googleapis.com/auth/adwords'],
            'configuration' => ['customer_id' => '123-456-7890'],
        ]);

        $this->providers->refreshCredentials($provider->fresh(['credential']));

        $credential = $provider->fresh()->credential;
        $this->assertSame('refreshed-access-token', $credential->access_token);
        $this->assertSame('durable-refresh', $credential->refresh_token);
        $this->assertSame('123-456-7890', $credential->configuration['customer_id']);
        $this->assertTrue($credential->expires_at->greaterThan(now()->addMinutes(55)));

        Http::assertSent(fn ($request) => $request->url() === 'https://oauth2.googleapis.com/token'
            && $request['grant_type'] === 'refresh_token'
            && $request['refresh_token'] === 'durable-refresh');
    }

    public function test_revoked_refresh_token_failure_is_normalized_without_overwriting_credentials(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Token has been expired or revoked.',
            ], 400),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->registerConnection($organization);
        $this->providers->storeCredentials($provider, [
            'access_token' => 'old-access',
            'refresh_token' => 'revoked-refresh',
            'expires_at' => now()->addMinutes(5),
        ]);

        try {
            $this->providers->refreshCredentials($provider->fresh(['credential']));
            $this->fail('Expected revoked refresh token failure.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('invalid_grant', $e->getMessage());
            $this->assertStringContainsString('revoked', $e->getMessage());
        }

        $this->assertSame('old-access', $provider->fresh()->credential->access_token);
        $this->assertSame('revoked-refresh', $provider->fresh()->credential->refresh_token);
    }

    public function test_health_check_validates_token_refresh_capability_and_ads_api_reachability(): void
    {
        Http::fake([
            'oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'healthy-google-user',
                'expires_in' => '3500',
            ]),
            'googleads.googleapis.com/v22/customers:listAccessibleCustomers' => Http::response([
                'resourceNames' => ['customers/111', 'customers/222'],
            ]),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->registerConnection($organization);
        $this->providers->storeCredentials($provider, [
            'access_token' => 'healthy-access',
            'refresh_token' => 'healthy-refresh',
            'expires_at' => now()->addHour(),
        ]);

        $result = $this->providers->checkHealth($provider->fresh(['credential']));

        $this->assertTrue($result['healthy']);
        $this->assertSame(MarketingProvider::STATUS_CONNECTED, $result['status']);
        $this->assertTrue($result['metadata']['refresh_capable']);
        $this->assertTrue($result['metadata']['api_reachable']);
        $this->assertSame(2, $result['metadata']['accessible_customer_count']);
        $this->assertNotNull($provider->fresh()->last_health_at);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'customers:listAccessibleCustomers')
            && $request->hasHeader('Authorization', 'Bearer healthy-access')
            && $request->hasHeader('developer-token', 'google-developer-token'));
    }

    public function test_health_check_maps_expired_and_revoked_tokens_to_canonical_expired_status(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->registerConnection($organization);
        $this->providers->storeCredentials($provider, [
            'access_token' => 'expired-access',
            'refresh_token' => 'refresh',
            'expires_at' => now()->subMinute(),
        ]);

        $expired = $this->providers->checkHealth($provider->fresh(['credential']));
        $this->assertFalse($expired['healthy']);
        $this->assertSame(MarketingProvider::STATUS_EXPIRED, $provider->fresh()->status);

        Http::fake([
            'oauth2.googleapis.com/tokeninfo*' => Http::response([
                'error' => 'invalid_token',
                'error_description' => 'Token expired or revoked.',
            ], 401),
        ]);

        $this->providers->storeCredentials($provider->fresh(), [
            'access_token' => 'revoked-access',
            'refresh_token' => 'refresh',
            'expires_at' => now()->addHour(),
        ]);
        $revoked = $this->providers->checkHealth($provider->fresh(['credential']));

        $this->assertFalse($revoked['healthy']);
        $this->assertSame(MarketingProvider::STATUS_EXPIRED, $provider->fresh()->status);
    }

    public function test_disconnect_revokes_remotely_and_clears_local_credentials(): void
    {
        Http::fake([
            'oauth2.googleapis.com/revoke' => Http::response([], 200),
        ]);

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->registerConnection($organization);
        $this->providers->storeCredentials($provider, [
            'access_token' => 'disconnect-access',
            'refresh_token' => 'disconnect-refresh',
            'expires_at' => now()->addHour(),
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('integrations.disconnect', ['provider' => 'google_ads']))
            ->assertRedirect(route('integrations.index'));

        $this->assertSame(MarketingProvider::STATUS_DISCONNECTED, $provider->fresh()->status);
        $this->assertNull($provider->fresh()->credential);
        Http::assertSent(fn ($request) => $request->url() === 'https://oauth2.googleapis.com/revoke'
            && $request['token'] === 'disconnect-refresh');
    }

    public function test_google_ads_credentials_and_ui_are_tenant_isolated(): void
    {
        [$userA, $orgA] = $this->setupUserWithOrg();
        [, $orgB] = $this->setupUserWithOrg();

        app(TenantContext::class)->set($orgA);
        $providerA = $this->registerConnection($orgA);
        $this->providers->storeCredentials($providerA, [
            'access_token' => 'org-a-google-access',
            'refresh_token' => 'org-a-google-refresh',
            'expires_at' => now()->addHour(),
            'configuration' => ['customer_id' => '111-222-3333'],
            'external_account_id' => 'google-a',
        ]);

        app(TenantContext::class)->set($orgB);
        $providerB = $this->registerConnection($orgB);
        $this->providers->storeCredentials($providerB, [
            'access_token' => 'org-b-google-access',
            'refresh_token' => 'org-b-google-refresh',
            'expires_at' => now()->addHour(),
            'configuration' => ['customer_id' => '999-888-7777'],
            'external_account_id' => 'google-b',
        ]);

        $this->assertNull($this->providers->findProviderForOrganization($orgB, $providerA->id));

        app(TenantContext::class)->set($orgA);
        $response = $this->actingAs($userA)
            ->withSession(['current_organization_id' => $orgA->id])
            ->get(route('integrations.show', ['provider' => 'google_ads']));

        $response->assertOk();
        $response->assertSee('Google Ads');
        $response->assertSee('Connected');
        $response->assertSee('google-a');
        $response->assertDontSee('google-b');
        $response->assertDontSee('org-a-google-access');
        $response->assertDontSee('org-a-google-refresh');
        $response->assertDontSee('org-b-google-access');
    }

    public function test_out_of_scope_google_ads_operations_remain_unsupported(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->registerConnection($organization);

        $this->assertFalse($this->google->synchronize($provider)['ok']);
        $this->assertFalse($this->google->receiveWebhook($provider, [])['ok']);
        $this->assertContains('offline_conversions', $this->google->capabilities());
        $this->assertNotContains('lead_import', $this->google->capabilities());
        $this->assertNotContains('webhooks', $this->google->capabilities());
    }
}
