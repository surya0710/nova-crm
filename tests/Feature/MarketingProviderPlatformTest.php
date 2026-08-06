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
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\Support\FakeMarketingProvider;
use Tests\TestCase;

class MarketingProviderPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingProviderRegistry $registry;

    protected MarketingProviderService $providers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = app(MarketingProviderRegistry::class);
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

    protected function registerFake(string $slug = 'fake'): FakeMarketingProvider
    {
        $fake = new FakeMarketingProvider($slug);
        $this->registry->register($fake);

        return $fake;
    }

    public function test_registry_registers_and_resolves_providers(): void
    {
        $fake = $this->registerFake();

        $this->assertTrue($this->registry->has('fake'));
        $this->assertSame($fake, $this->registry->resolve('fake'));
        $this->assertContains('fake', $this->registry->slugs());
        $this->assertContains('meta', $this->registry->slugs());

        $supported = collect($this->registry->supported())->keyBy('slug');
        $this->assertSame('Fake Provider', $supported['fake']['name']);
        $this->assertSame(['oauth', 'sync', 'webhooks', 'offline_conversions'], $supported['fake']['capabilities']);
        $this->assertSame('Meta Business', $supported['meta']['name']);
        $this->assertSame(['oauth', 'asset_discovery', 'lead_form_sync', 'lead_import', 'webhooks', 'offline_conversions'], $supported['meta']['capabilities']);
    }

    public function test_registry_rejects_unknown_provider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Marketing provider [not_a_real_provider] is not registered.');

        $this->registry->resolve('not_a_real_provider');
    }

    public function test_meta_driver_is_registered_via_config(): void
    {
        $this->assertArrayHasKey('meta', config('marketing.providers.drivers'));
        $this->assertTrue($this->registry->has('meta'));
        $this->assertInstanceOf(
            MetaMarketingProvider::class,
            $this->registry->resolve('meta')
        );
        $this->assertArrayHasKey('meta', $this->providers->catalog());
        $this->assertArrayHasKey('google_ads', $this->providers->catalog());
        $this->assertArrayHasKey('linkedin', $this->providers->catalog());
    }

    public function test_register_provider_creates_disconnected_connection(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->registerFake();

        $provider = $this->providers->registerProvider($organization, 'fake');

        $this->assertSame($organization->id, $provider->organization_id);
        $this->assertSame('fake', $provider->slug);
        $this->assertSame(MarketingProvider::STATUS_DISCONNECTED, $provider->status);
        $this->assertNull($provider->connected_at);
        $this->assertSame(['oauth', 'sync', 'webhooks', 'offline_conversions'], $provider->capabilities);
    }

    public function test_register_provider_is_idempotent_per_org_slug(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->registerFake();

        $first = $this->providers->registerProvider($organization, 'fake');
        $second = $this->providers->registerProvider($organization, 'fake');

        $this->assertTrue($first->is($second));
        $this->assertSame(1, MarketingProvider::query()->where('organization_id', $organization->id)->count());
    }

    public function test_store_credentials_encrypts_secrets_and_marks_connected(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->registerFake();

        $provider = $this->providers->registerProvider($organization, 'fake');
        $credential = $this->providers->storeCredentials($provider, [
            'access_token' => 'plain-access-token',
            'refresh_token' => 'plain-refresh-token',
            'token_type' => 'Bearer',
            'scopes' => ['ads_read'],
            'expires_at' => now()->addDay(),
            'external_account_id' => 'ext-123',
        ]);

        $this->assertSame('plain-access-token', $credential->access_token);
        $this->assertSame('plain-refresh-token', $credential->refresh_token);

        $row = DB::table('marketing_provider_credentials')->where('id', $credential->id)->first();
        $this->assertNotSame('plain-access-token', $row->access_token);
        $this->assertNotSame('plain-refresh-token', $row->refresh_token);
        $this->assertStringNotContainsString('plain-access-token', (string) $row->access_token);
        $this->assertStringNotContainsString('plain-refresh-token', (string) $row->refresh_token);

        $provider->refresh();
        $this->assertSame(MarketingProvider::STATUS_CONNECTED, $provider->status);
        $this->assertSame('ext-123', $provider->external_account_id);
        $this->assertNotNull($provider->connected_at);
        $this->assertNull($provider->last_error);
    }

    public function test_store_expired_credentials_marks_expired(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->registerFake();

        $provider = $this->providers->registerProvider($organization, 'fake');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'expired-token',
            'expires_at' => now()->subMinute(),
        ]);

        $provider->refresh();
        $this->assertSame(MarketingProvider::STATUS_EXPIRED, $provider->status);
        $this->assertSame('Credentials expired', $provider->last_error);
    }

    public function test_clear_credentials_and_disconnect_lifecycle(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $fake = $this->registerFake();

        $provider = $this->providers->registerProvider($organization, 'fake');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'token',
            'expires_at' => now()->addHour(),
        ]);

        $disconnected = $this->providers->disconnect($provider->fresh());

        $this->assertTrue($fake->revokeCalled);
        $this->assertSame(MarketingProvider::STATUS_DISCONNECTED, $disconnected->status);
        $this->assertNull($disconnected->connected_at);
        $this->assertNull($disconnected->credential);
        $this->assertSame(0, MarketingProviderCredential::query()->where('marketing_provider_id', $provider->id)->count());
    }

    public function test_health_state_transitions(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = MarketingProvider::factory()->forOrganization($organization)->create([
            'slug' => 'fake',
            'status' => MarketingProvider::STATUS_DISCONNECTED,
        ]);

        $this->assertSame(MarketingProvider::STATUS_CONNECTED, $this->providers->markConnected($provider)->status);
        $this->assertSame(MarketingProvider::STATUS_EXPIRED, $this->providers->markExpired($provider->fresh())->status);
        $this->assertSame(MarketingProvider::STATUS_ERROR, $this->providers->markError($provider->fresh(), 'boom')->status);
        $this->assertSame('boom', $provider->fresh()->last_error);
        $this->assertSame(MarketingProvider::STATUS_DISCONNECTED, $this->providers->markDisconnected($provider->fresh())->status);
        $this->assertNull($provider->fresh()->last_error);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MarketingProvider::assertValidStatus('pending');
    }

    public function test_check_health_updates_status_from_adapter(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $fake = $this->registerFake();

        $provider = $this->providers->registerProvider($organization, 'fake');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'token',
            'expires_at' => now()->addHour(),
        ]);

        $ok = $this->providers->checkHealth($provider->fresh());
        $this->assertTrue($ok['healthy']);
        $this->assertSame(MarketingProvider::STATUS_CONNECTED, $ok['status']);
        $this->assertSame(1, $fake->healthCalls);

        $fake->healthy = false;
        $fake->healthStatus = MarketingProvider::STATUS_ERROR;
        $fake->healthMessage = 'API down';

        $bad = $this->providers->checkHealth($provider->fresh());
        $this->assertFalse($bad['healthy']);
        $this->assertSame(MarketingProvider::STATUS_ERROR, $provider->fresh()->status);
        $this->assertSame('API down', $provider->fresh()->last_error);
        $this->assertNotNull($provider->fresh()->last_health_at);
    }

    public function test_authorize_and_refresh_persist_via_service(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $fake = $this->registerFake();
        $fake->authorizeResult = [
            'credentials' => [
                'access_token' => 'auth-access',
                'refresh_token' => 'auth-refresh',
                'expires_at' => now()->addHours(2),
                'token_type' => 'Bearer',
                'scopes' => ['ads_read'],
            ],
        ];

        $provider = $this->providers->registerProvider($organization, 'fake');
        $this->providers->authorize($provider);

        $this->assertSame(1, $fake->authorizeCalls);
        $this->assertSame(MarketingProvider::STATUS_CONNECTED, $provider->fresh()->status);
        $this->assertSame('auth-access', $provider->fresh()->credential->access_token);

        $this->providers->refreshCredentials($provider->fresh());
        $this->assertSame(1, $fake->refreshCalls);
        $this->assertSame('refreshed-access', $provider->fresh()->credential->access_token);
    }

    public function test_synchronize_orchestration_updates_last_synced_at(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $fake = $this->registerFake();

        $provider = $this->providers->registerProvider($organization, 'fake');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'token',
            'expires_at' => now()->addHour(),
        ]);

        $result = $this->providers->synchronize($provider->fresh());

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $fake->syncCalls);
        $this->assertNotNull($provider->fresh()->last_synced_at);
    }

    public function test_synchronize_failure_marks_error(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $fake = $this->registerFake();
        $fake->syncOk = false;

        $provider = $this->providers->registerProvider($organization, 'fake');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'token',
            'expires_at' => now()->addHour(),
        ]);

        $result = $this->providers->synchronize($provider->fresh());

        $this->assertFalse($result['ok']);
        $this->assertSame(MarketingProvider::STATUS_ERROR, $provider->fresh()->status);
        $this->assertSame('sync failed', $provider->fresh()->last_error);
    }

    public function test_webhook_and_conversion_upload_delegate_to_adapter(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $fake = $this->registerFake();

        $provider = $this->providers->registerProvider($organization, 'fake');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'token',
            'expires_at' => now()->addHour(),
        ]);

        $webhook = $this->providers->receiveWebhook($provider->fresh(), ['event' => 'leadgen'], ['X-Sig' => '1']);
        $upload = $this->providers->uploadConversions($provider->fresh(), [['event_name' => 'lead_created']]);

        $this->assertTrue($webhook['ok']);
        $this->assertSame('leadgen', $webhook['event']);
        $this->assertFalse($webhook['duplicate'] ?? true);
        $this->assertNotNull($webhook['webhook_event_id'] ?? null);
        $this->assertDatabaseHas('marketing_provider_webhook_events', [
            'id' => $webhook['webhook_event_id'],
            'provider' => 'fake',
            'organization_id' => $organization->id,
            'processing_status' => 'received',
        ]);
        $this->assertTrue($upload['ok']);
        $this->assertSame(1, $upload['uploaded']);
        $this->assertSame(1, $fake->webhookCalls);
        $this->assertSame(1, $fake->uploadCalls);
    }

    public function test_providers_are_organization_scoped(): void
    {
        [, $orgA] = $this->setupUserWithOrg();
        [, $orgB] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($orgA);
        $this->registerFake();

        $providerA = $this->providers->registerProvider($orgA, 'fake');
        $this->providers->storeCredentials($providerA, [
            'access_token' => 'org-a-token',
            'expires_at' => now()->addHour(),
        ]);

        app(TenantContext::class)->set($orgB);
        $providerB = $this->providers->registerProvider($orgB, 'fake');

        $this->assertNotSame($providerA->id, $providerB->id);
        $this->assertNull($this->providers->findProviderForOrganization($orgB, $providerA->id));

        app(TenantContext::class)->set($orgA);
        $this->assertTrue($this->providers->findProviderForOrganization($orgA, $providerA->id)?->is($providerA));
        $this->assertCount(1, $this->providers->listProviders($orgA));
        $this->assertSame($providerA->id, $this->providers->listProviders($orgA)->first()->id);

        app(TenantContext::class)->set($orgB);
        $this->assertCount(1, $this->providers->listProviders($orgB));
        $this->assertSame($providerB->id, $this->providers->listProviders($orgB)->first()->id);
        $this->assertNull($this->providers->findProvider($orgB, 'missing'));
    }

    public function test_credentials_hidden_from_array_serialization(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = MarketingProvider::factory()->forOrganization($organization)->connected()->create(['slug' => 'fake']);
        $credential = MarketingProviderCredential::factory()->forProvider($provider)->create([
            'access_token' => 'secret-access',
            'refresh_token' => 'secret-refresh',
        ]);

        $array = $credential->toArray();
        $this->assertArrayNotHasKey('access_token', $array);
        $this->assertArrayNotHasKey('refresh_token', $array);
    }

    public function test_supported_statuses_match_canonical_set(): void
    {
        $this->assertSame(
            ['connected', 'disconnected', 'expired', 'error'],
            $this->providers->supportedStatuses()
        );
        $this->assertSame(
            config('marketing.providers.statuses'),
            $this->providers->supportedStatuses()
        );
    }

    public function test_marketing_tracking_runtime_unaffected(): void
    {
        $before = MarketingVisitor::query()->count();

        $tracking = app(MarketingTrackingService::class);
        $visitor = $tracking->createVisitor(['ip' => '203.0.113.90']);
        $session = $tracking->createSession($visitor);
        $tracking->recordPageView($session, [
            'url' => 'https://example.test/provider-foundation',
            'referrer' => null,
        ]);

        $this->assertSame($before + 1, MarketingVisitor::query()->count());
        $this->assertSame(0, MarketingProvider::query()->count());
    }

    public function test_adapter_required_for_health_without_registration(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = $this->providers->registerProvider($organization, 'unregistered_vendor', [
            'display_name' => 'Unregistered Vendor',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Marketing provider [unregistered_vendor] has no registered adapter.');

        $this->providers->checkHealth($provider);
    }
}
