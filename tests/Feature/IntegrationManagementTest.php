<?php

namespace Tests\Feature;

use App\Models\MarketingProvider;
use App\Models\MarketingProviderCredential;
use App\Models\Organization;
use App\Models\User;
use App\Services\MarketingProviderService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IntegrationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingProviderService $providers;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_integrations_index_is_provider_agnostic_and_lists_catalog(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('integrations.index'));

        $response->assertOk();
        $response->assertSee('Integrations');
        $response->assertSee('Marketing');
        $response->assertSee('Meta Business');
        $response->assertSee('Google Ads');
        $response->assertSee('LinkedIn Ads');
        $response->assertSee('Microsoft Ads');
        $response->assertSee('TikTok Ads');
        $response->assertSee('Connect');
        $response->assertSee('Coming soon');
        $response->assertSee('View Details');
        $response->assertDontSee('plain-access-token');
    }

    public function test_employee_cannot_view_integrations(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('employee');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('integrations.index'))
            ->assertForbidden();
    }

    public function test_manager_can_view_integrations(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('integrations.index'))
            ->assertOk();
    }

    public function test_integration_cards_reflect_status_lifecycle(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $cards = collect($this->providers->integrationCardsForOrganization($organization))->keyBy('slug');
        $this->assertSame('disconnected', $cards['meta']['status']);
        $this->assertTrue($cards['meta']['connectable']);
        $this->assertTrue($cards['google_ads']['connectable']);

        $provider = $this->providers->registerProvider($organization, 'meta');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'tenant-secret-token',
            'refresh_token' => 'tenant-refresh',
            'expires_at' => now()->addDay(),
            'configuration' => ['ad_account_id' => 'act_123'],
            'external_account_id' => 'ext-1',
        ]);

        $cards = collect($this->providers->integrationCardsForOrganization($organization))->keyBy('slug');
        $this->assertSame('connected', $cards['meta']['status']);
        $this->assertSame('Connected', $cards['meta']['status_label']);
        $this->assertSame('ext-1', $cards['meta']['external_account_id']);
        $this->assertSame(['ad_account_id' => 'act_123'], $cards['meta']['configuration']);
        $this->assertArrayNotHasKey('access_token', $cards['meta']);

        $this->providers->markExpired($provider->fresh());
        $cards = collect($this->providers->integrationCardsForOrganization($organization))->keyBy('slug');
        $this->assertSame('expired', $cards['meta']['status']);

        $this->providers->disconnect($provider->fresh());
        $cards = collect($this->providers->integrationCardsForOrganization($organization))->keyBy('slug');
        $this->assertSame('disconnected', $cards['meta']['status']);
        $this->assertNotNull($cards['meta']['disconnected_at']);
        $this->assertNull($cards['meta']['external_account_id']);
    }

    public function test_tenant_credentials_are_encrypted_and_isolated(): void
    {
        [, $orgA] = $this->setupUserWithOrg();
        [, $orgB] = $this->setupUserWithOrg();

        app(TenantContext::class)->set($orgA);
        $providerA = $this->providers->registerProvider($orgA, 'meta');
        $this->providers->storeCredentials($providerA, [
            'access_token' => 'org-a-secret',
            'expires_at' => now()->addDay(),
            'configuration' => ['business_id' => 'biz-a'],
        ]);

        app(TenantContext::class)->set($orgB);
        $providerB = $this->providers->registerProvider($orgB, 'meta');
        $this->providers->storeCredentials($providerB, [
            'access_token' => 'org-b-secret',
            'expires_at' => now()->addDay(),
            'configuration' => ['business_id' => 'biz-b'],
        ]);

        $this->assertNotSame($providerA->id, $providerB->id);

        app(TenantContext::class)->set($orgA);
        $this->assertSame('org-a-secret', $providerA->fresh()->credential->access_token);

        app(TenantContext::class)->set($orgB);
        $this->assertSame('org-b-secret', $providerB->fresh()->credential->access_token);

        $rowA = DB::table('marketing_provider_credentials')->where('marketing_provider_id', $providerA->id)->first();
        $this->assertNotSame('org-a-secret', $rowA->access_token);
        $this->assertStringNotContainsString('org-a-secret', (string) $rowA->access_token);

        app(TenantContext::class)->set($orgA);
        $this->assertNull($this->providers->findProviderForOrganization($orgA, $providerB->id));
        $this->assertSame('biz-a', $this->providers->findProvider($orgA, 'meta')?->credential?->configuration['business_id']);

        app(TenantContext::class)->set($orgB);
        $this->assertSame('biz-b', $this->providers->findProvider($orgB, 'meta')?->credential?->configuration['business_id']);
    }

    public function test_details_page_never_exposes_tokens(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = $this->providers->registerProvider($organization, 'meta');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'must-not-appear',
            'refresh_token' => 'refresh-must-not-appear',
            'expires_at' => now()->addDay(),
            'configuration' => ['pixel_id' => 'px_9'],
            'external_account_id' => 'acct-9',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('integrations.show', ['provider' => 'meta']));

        $response->assertOk();
        $response->assertSee('Meta Business');
        $response->assertSee('Connected');
        $response->assertSee('acct-9');
        $response->assertSee('px_9');
        $response->assertSee('encrypted at rest');
        $response->assertDontSee('must-not-appear');
        $response->assertDontSee('refresh-must-not-appear');
    }

    public function test_ui_disconnect_uses_provider_service(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $provider = $this->providers->registerProvider($organization, 'meta');
        $this->providers->storeCredentials($provider, [
            'access_token' => 'token',
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('integrations.disconnect', ['provider' => 'meta']))
            ->assertRedirect(route('integrations.index'));

        $this->assertSame(MarketingProvider::STATUS_DISCONNECTED, $provider->fresh()->status);
        $this->assertSame(0, MarketingProviderCredential::query()->where('marketing_provider_id', $provider->id)->count());
        $this->assertNotNull($provider->fresh()->disconnected_at);
    }

    public function test_sidebar_shows_integrations_for_permitted_users(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Integrations');
    }
}
