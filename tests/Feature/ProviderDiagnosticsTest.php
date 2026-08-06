<?php

namespace Tests\Feature;

use App\Models\MarketingProvider;
use App\Models\MarketingProviderCredential;
use App\Models\MarketingProviderImportedLead;
use App\Models\MarketingProviderLeadImportRun;
use App\Models\MarketingProviderSyncRun;
use App\Models\MarketingProviderUploadedConversion;
use App\Models\MarketingProviderWebhookEvent;
use App\Models\Organization;
use App\Models\User;
use App\Services\Marketing\Providers\MarketingProviderRegistry;
use App\Services\MarketingProviderService;
use App\Services\ProviderDiagnosticsService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeMarketingProvider;
use Tests\TestCase;

class ProviderDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    protected ProviderDiagnosticsService $diagnostics;

    protected MarketingProviderService $providers;

    protected MarketingProviderRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diagnostics = app(ProviderDiagnosticsService::class);
        $this->providers = app(MarketingProviderService::class);
        $this->registry = app(MarketingProviderRegistry::class);
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

    public function test_diagnostics_aggregate_all_catalog_providers(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $payload = $this->diagnostics->diagnosticsForOrganization($organization);

        $this->assertArrayHasKey('generated_at', $payload);
        $this->assertSame($organization->id, $payload['organization_id']);
        $this->assertGreaterThanOrEqual(5, count($payload['providers']));
        $this->assertArrayHasKey('summary', $payload);

        $slugs = collect($payload['providers'])->pluck('slug')->all();
        $this->assertContains('meta', $slugs);
        $this->assertContains('google_ads', $slugs);
    }

    public function test_connected_provider_diagnostics_include_health_credentials_sync_and_statistics(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->registry->register(new FakeMarketingProvider('meta', 'Meta Business'));

        $provider = $this->providers->registerProvider($organization, 'meta');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'token',
            'refresh_token' => 'refresh',
            'expires_at' => now()->addDay(),
        ]);

        $run = MarketingProviderSyncRun::factory()
            ->forProvider($provider)
            ->create([
                'sync_type' => MarketingProviderSyncRun::TYPE_CONVERSION_UPLOAD,
                'direction' => MarketingProviderSyncRun::DIRECTION_OUTBOUND,
                'status' => MarketingProviderSyncRun::STATUS_COMPLETED,
                'started_at' => now()->subMinutes(5),
                'finished_at' => now()->subMinutes(4),
                'records_processed' => 3,
                'records_succeeded' => 3,
                'records_failed' => 0,
            ]);

        MarketingProviderImportedLead::factory()->create([
            'organization_id' => $organization->id,
            'marketing_provider_id' => $provider->id,
        ]);

        MarketingProviderUploadedConversion::factory()->create([
            'organization_id' => $organization->id,
            'marketing_provider_id' => $provider->id,
        ]);

        MarketingProviderWebhookEvent::factory()->create([
            'organization_id' => $organization->id,
            'provider' => 'meta',
            'processing_status' => MarketingProviderWebhookEvent::STATUS_PROCESSED,
        ]);

        MarketingProviderLeadImportRun::factory()->create([
            'organization_id' => $organization->id,
            'marketing_provider_id' => $provider->id,
            'status' => MarketingProviderLeadImportRun::STATUS_COMPLETED,
            'imported_at' => now()->subHour(),
        ]);

        $provider->update(['last_health_at' => now()->subMinutes(10)]);

        $diagnostics = collect($this->diagnostics->diagnosticsForOrganization($organization)['providers'])
            ->firstWhere('slug', 'meta');

        $this->assertNotNull($diagnostics);
        $this->assertSame('connected', $diagnostics['connection']['status']);
        $this->assertSame(ProviderDiagnosticsService::HEALTH_HEALTHY, $diagnostics['health']['state']);
        $this->assertTrue($diagnostics['health']['healthy']);
        $this->assertTrue($diagnostics['credentials']['oauth_connected']);
        $this->assertTrue($diagnostics['credentials']['refresh_token_available']);
        $this->assertSame(1, $diagnostics['statistics']['inbound']['imported_leads']);
        $this->assertSame(1, $diagnostics['statistics']['inbound']['webhook_events_processed']);
        $this->assertSame(1, $diagnostics['statistics']['outbound']['uploaded_conversions']);
        $this->assertSame(1, $diagnostics['statistics']['general']['synchronization_count']);
        $this->assertSame(1, $diagnostics['statistics']['general']['success_count']);
        $this->assertSame('conversion_upload', $diagnostics['synchronization']['last_upload']['sync_type']);
        $this->assertSame($run->id, $diagnostics['synchronization']['last']['id']);
        $this->assertNotNull($diagnostics['highlights']['last_upload_at']);
        $this->assertNotNull($diagnostics['highlights']['last_import_at']);
        $this->assertNotNull($diagnostics['highlights']['last_health_check_at']);
    }

    public function test_synchronization_summary_tracks_success_and_failure(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->registry->register(new FakeMarketingProvider('meta', 'Meta Business'));

        $provider = $this->providers->registerProvider($organization, 'meta');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'token',
            'expires_at' => now()->addDay(),
        ]);

        MarketingProviderSyncRun::factory()->forProvider($provider)->create([
            'sync_type' => MarketingProviderSyncRun::TYPE_FORM_SYNC,
            'status' => MarketingProviderSyncRun::STATUS_FAILED,
            'started_at' => now()->subHours(2),
            'finished_at' => now()->subHours(2)->addMinute(),
            'records_processed' => 1,
            'records_failed' => 1,
            'message' => 'Synchronization failure',
        ]);

        MarketingProviderSyncRun::factory()->forProvider($provider)->create([
            'sync_type' => MarketingProviderSyncRun::TYPE_ASSET_DISCOVERY,
            'status' => MarketingProviderSyncRun::STATUS_PARTIAL,
            'started_at' => now()->subHour(),
            'finished_at' => now()->subHour()->addSeconds(30),
            'records_processed' => 4,
            'records_succeeded' => 3,
            'records_failed' => 1,
        ]);

        $diagnostics = $this->diagnostics->diagnosticsForProvider($provider->fresh());

        $this->assertSame(ProviderDiagnosticsService::HEALTH_DEGRADED, $diagnostics['health']['state']);
        $this->assertSame('asset_discovery', $diagnostics['synchronization']['last']['sync_type']);
        $this->assertSame('asset_discovery', $diagnostics['synchronization']['last_successful']['sync_type']);
        $this->assertSame('form_sync', $diagnostics['synchronization']['last_failed']['sync_type']);
        $this->assertSame(30, $diagnostics['synchronization']['last']['duration_seconds']);
        $this->assertNotEmpty($diagnostics['errors']);
        $this->assertSame('Synchronization failure', $diagnostics['errors'][0]['message']);
    }

    public function test_expired_credentials_normalize_to_expired_health_state(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->registry->register(new FakeMarketingProvider('meta', 'Meta Business'));

        $provider = $this->providers->registerProvider($organization, 'meta');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'token',
            'expires_at' => now()->subMinute(),
        ]);

        $diagnostics = $this->diagnostics->diagnosticsForProvider($provider->fresh());

        $this->assertSame(ProviderDiagnosticsService::HEALTH_EXPIRED_CREDENTIALS, $diagnostics['health']['state']);
        $this->assertTrue($diagnostics['credentials']['is_expired']);
        $this->assertFalse($diagnostics['credentials']['oauth_connected']);
    }

    public function test_revoked_credentials_normalize_to_revoked_health_state(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->registry->register(new FakeMarketingProvider('meta', 'Meta Business'));

        $provider = $this->providers->registerProvider($organization, 'meta');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'token',
            'expires_at' => now()->addDay(),
            'metadata' => ['revoked' => true],
        ]);
        $this->providers->markError($provider->fresh(), 'OAuth token has been revoked');

        $diagnostics = $this->diagnostics->diagnosticsForProvider($provider->fresh());

        $this->assertSame(ProviderDiagnosticsService::HEALTH_REVOKED_CREDENTIALS, $diagnostics['health']['state']);
        $this->assertTrue($diagnostics['credentials']['is_revoked']);
    }

    public function test_run_health_check_invokes_adapter_and_updates_diagnostics(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $fake = new FakeMarketingProvider('meta', 'Meta Business');
        $this->registry->register($fake);

        $provider = $this->providers->registerProvider($organization, 'meta');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'token',
            'expires_at' => now()->addHour(),
        ]);

        $result = $this->diagnostics->runHealthCheck($provider->fresh());

        $this->assertSame(1, $fake->healthCalls);
        $this->assertTrue($result['health_check']['healthy']);
        $this->assertSame(ProviderDiagnosticsService::HEALTH_HEALTHY, $result['diagnostics']['health']['state']);
        $this->assertNotNull($provider->fresh()->last_health_at);
    }

    public function test_diagnostics_dashboard_renders_for_authorized_user(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $this->registry->register(new FakeMarketingProvider('meta', 'Meta Business'));

        $provider = $this->providers->registerProvider($organization, 'meta');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'token',
            'expires_at' => now()->addDay(),
        ]);

        MarketingProviderSyncRun::factory()->forProvider($provider)->create([
            'sync_type' => MarketingProviderSyncRun::TYPE_CONVERSION_UPLOAD,
            'status' => MarketingProviderSyncRun::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(2),
            'finished_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('integrations.diagnostics'));

        $response->assertOk();
        $response->assertSee('Diagnostics & Health');
        $response->assertSee('Meta Business');
        $response->assertSee('Google Ads');
        $response->assertSee('Run Health Check');
        $response->assertSee('Synchronization Summary');
        $response->assertDontSee('plain-access-token');
    }

    public function test_manual_health_check_action_updates_status_and_redirects(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $fake = new FakeMarketingProvider('meta', 'Meta Business');
        $this->registry->register($fake);

        $provider = $this->providers->registerProvider($organization, 'meta');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'token',
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('integrations.health-check', ['provider' => 'meta']));

        $response->assertRedirect(route('integrations.diagnostics'));
        $response->assertSessionHas('status', 'integration-health-check-healthy');
        $this->assertSame(1, $fake->healthCalls);
        $this->assertNotNull($provider->fresh()->last_health_at);
    }

    public function test_employee_cannot_view_diagnostics_or_run_health_checks(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('employee');
        $this->registerFake('fake');
        $this->providers->registerProvider($organization, 'fake');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('integrations.diagnostics'))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('integrations.health-check', ['provider' => 'fake']))
            ->assertForbidden();
    }

    public function test_tenant_isolation_for_diagnostics_aggregation(): void
    {
        [, $orgA] = $this->setupUserWithOrg();
        [, $orgB] = $this->setupUserWithOrg();
        $this->registry->register(new FakeMarketingProvider('meta', 'Meta Business'));

        app(TenantContext::class)->set($orgA);
        $providerA = $this->providers->registerProvider($orgA, 'meta');
        $this->providers->storeCredentials($providerA, [
            'access_token' => 'org-a-token',
            'expires_at' => now()->addDay(),
        ]);
        MarketingProviderImportedLead::factory()->count(2)->create([
            'organization_id' => $orgA->id,
            'marketing_provider_id' => $providerA->id,
        ]);

        app(TenantContext::class)->set($orgB);
        $providerB = $this->providers->registerProvider($orgB, 'meta');
        $this->providers->storeCredentials($providerB, [
            'access_token' => 'org-b-token',
            'expires_at' => now()->addDay(),
        ]);
        MarketingProviderImportedLead::factory()->create([
            'organization_id' => $orgB->id,
            'marketing_provider_id' => $providerB->id,
        ]);

        $diagnosticsA = collect($this->diagnostics->diagnosticsForOrganization($orgA)['providers'])
            ->firstWhere('slug', 'meta');
        $diagnosticsB = collect($this->diagnostics->diagnosticsForOrganization($orgB)['providers'])
            ->firstWhere('slug', 'meta');

        $this->assertSame(2, $diagnosticsA['statistics']['inbound']['imported_leads']);
        $this->assertSame(1, $diagnosticsB['statistics']['inbound']['imported_leads']);
        $this->assertSame($providerA->id, $diagnosticsA['provider_id']);
        $this->assertSame($providerB->id, $diagnosticsB['provider_id']);
        $this->assertNotSame($diagnosticsA['provider_id'], $diagnosticsB['provider_id']);
    }

    public function test_meta_and_google_providers_participate_without_custom_platform_code(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $meta = $this->providers->registerProvider($organization, 'meta');
        $this->providers->storeCredentials($meta, [
            'access_token' => 'meta-token',
            'expires_at' => now()->addDay(),
        ]);

        $google = $this->providers->registerProvider($organization, 'google_ads');
        $this->providers->storeCredentials($google, [
            'access_token' => 'google-token',
            'expires_at' => now()->addDay(),
        ]);

        $payload = $this->diagnostics->diagnosticsForOrganization($organization);
        $bySlug = collect($payload['providers'])->keyBy('slug');

        $this->assertSame('connected', $bySlug['meta']['connection']['status']);
        $this->assertSame('connected', $bySlug['google_ads']['connection']['status']);
        $this->assertArrayHasKey('health', $bySlug['meta']);
        $this->assertArrayHasKey('statistics', $bySlug['google_ads']);
    }

    public function test_unhealthy_provider_status_maps_to_unhealthy_health_state(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->registry->register(new FakeMarketingProvider('meta', 'Meta Business'));

        $provider = MarketingProvider::factory()->forOrganization($organization)->create([
            'slug' => 'meta',
            'status' => MarketingProvider::STATUS_ERROR,
            'last_error' => 'API unavailable',
        ]);
        MarketingProviderCredential::factory()->create([
            'organization_id' => $organization->id,
            'marketing_provider_id' => $provider->id,
            'access_token' => 'token',
            'expires_at' => now()->addDay(),
        ]);

        $diagnostics = $this->diagnostics->diagnosticsForProvider($provider->fresh(['credential']));

        $this->assertSame(ProviderDiagnosticsService::HEALTH_UNHEALTHY, $diagnostics['health']['state']);
        $this->assertSame('API unavailable', $diagnostics['errors'][0]['message']);
    }
}
