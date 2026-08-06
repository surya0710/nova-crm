<?php

namespace Tests\Feature;

use App\Models\MarketingProvider;
use App\Models\MarketingVisitor;
use App\Models\Organization;
use App\Models\User;
use App\Services\Marketing\Providers\MarketingProviderRegistry;
use App\Services\Marketing\Providers\MetaMarketingProvider;
use App\Services\MarketingProviderService;
use App\Services\MarketingTrackingService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaAssetDiscoveryTest extends TestCase
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
        $this->meta = app(MarketingProviderRegistry::class)->resolve('meta');
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

    protected function connectMeta(Organization $organization, string $token = 'meta-access-token'): MarketingProvider
    {
        $provider = $this->providers->registerProvider($organization, 'meta');

        $this->providers->storeCredentials($provider, [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_at' => now()->addDays(50),
            'external_account_id' => '100200300',
            'configuration' => [],
        ]);

        return $provider->fresh(['credential']);
    }

    protected function fakeDiscoveryGraph(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/me/businesses*' => Http::response([
                'data' => [
                    ['id' => 'biz_1', 'name' => 'Acme Business'],
                    ['id' => 'biz_2', 'name' => 'Other Business'],
                ],
            ]),
            'graph.facebook.com/v21.0/biz_1/owned_ad_accounts*' => Http::response([
                'data' => [
                    ['id' => 'act_111', 'account_id' => '111', 'name' => 'Acme Ads'],
                ],
            ]),
            'graph.facebook.com/v21.0/biz_1/client_ad_accounts*' => Http::response(['data' => []]),
            'graph.facebook.com/v21.0/biz_1/owned_pages*' => Http::response([
                'data' => [
                    ['id' => 'page_1', 'name' => 'Acme Page'],
                ],
            ]),
            'graph.facebook.com/v21.0/biz_1/client_pages*' => Http::response(['data' => []]),
            'graph.facebook.com/v21.0/biz_1/owned_pixels*' => Http::response([
                'data' => [
                    ['id' => 'pixel_1', 'name' => 'Acme Pixel'],
                ],
            ]),
            'graph.facebook.com/v21.0/biz_2/owned_ad_accounts*' => Http::response(['data' => []]),
            'graph.facebook.com/v21.0/biz_2/client_ad_accounts*' => Http::response(['data' => []]),
            'graph.facebook.com/v21.0/biz_2/owned_pages*' => Http::response(['data' => []]),
            'graph.facebook.com/v21.0/biz_2/client_pages*' => Http::response(['data' => []]),
            'graph.facebook.com/v21.0/biz_2/owned_pixels*' => Http::response(['data' => []]),
            'graph.facebook.com/v21.0/act_111/adspixels*' => Http::response(['data' => []]),
            'graph.facebook.com/v21.0/page_1/leadgen_forms*' => Http::response([
                'data' => [
                    ['id' => 'form_1', 'name' => 'Summer Lead Form', 'status' => 'ACTIVE'],
                    ['id' => 'form_2', 'name' => 'Winter Lead Form', 'status' => 'ACTIVE'],
                ],
            ]),
            'graph.facebook.com/v21.0/me/accounts*' => Http::response(['data' => []]),
        ]);
    }

    public function test_meta_declares_asset_discovery_capability(): void
    {
        $this->assertContains('asset_discovery', $this->meta->capabilities());
        $this->assertContains('oauth', $this->meta->capabilities());
        $this->assertContains('lead_form_sync', $this->meta->capabilities());
        $this->assertContains('lead_import', $this->meta->capabilities());
        $this->assertContains('webhooks', $this->meta->capabilities());
        $this->assertContains('offline_conversions', $this->meta->capabilities());
    }

    public function test_discovers_businesses_ad_accounts_pages_pixels_and_lead_forms(): void
    {
        $this->fakeDiscoveryGraph();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);

        $result = $this->providers->discoverAssets($provider);

        $this->assertTrue($result['ok']);
        $this->assertCount(2, $result['assets']['businesses']);
        $this->assertSame('Acme Business', $result['assets']['businesses'][0]['name']);
        $this->assertSame('act_111', $result['assets']['ad_accounts'][0]['id']);
        $this->assertSame('page_1', $result['assets']['pages'][0]['id']);
        $this->assertSame('pixel_1', $result['assets']['pixels'][0]['id']);
        $this->assertCount(2, $result['assets']['lead_forms']);
        $this->assertSame('form_1', $result['assets']['lead_forms'][0]['id']);
        $this->assertSame('page_1', $result['assets']['lead_forms'][0]['page_id']);
    }

    public function test_saves_and_updates_asset_selections_in_configuration_json(): void
    {
        $this->fakeDiscoveryGraph();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);

        $credential = $this->providers->saveAssetConfiguration($provider, [
            'business_id' => 'biz_1',
            'ad_account_id' => 'act_111',
            'page_id' => 'page_1',
            'pixel_id' => 'pixel_1',
            'lead_form_ids' => ['form_1', 'form_1', 'form_2'],
        ]);

        $this->assertSame('meta-access-token', $provider->fresh()->credential->access_token);
        $this->assertSame([
            'business_id' => 'biz_1',
            'ad_account_id' => 'act_111',
            'page_id' => 'page_1',
            'pixel_id' => 'pixel_1',
            'lead_form_ids' => ['form_1', 'form_2'],
        ], $credential->configuration);

        $updated = $this->providers->saveAssetConfiguration($provider->fresh(), [
            'business_id' => 'biz_1',
            'ad_account_id' => 'act_111',
            'page_id' => 'page_1',
            'pixel_id' => null,
            'lead_form_ids' => ['form_2'],
        ]);

        $this->assertSame(['form_2'], $updated->configuration['lead_form_ids']);
        $this->assertNull($updated->configuration['pixel_id']);
        $this->assertSame('meta-access-token', $provider->fresh()->credential->access_token);
    }

    public function test_rejects_invalid_and_unauthorized_asset_selections(): void
    {
        $this->fakeDiscoveryGraph();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);

        $this->providers->saveAssetConfiguration($provider, [
            'business_id' => 'biz_1',
            'ad_account_id' => 'act_111',
            'page_id' => 'page_1',
            'pixel_id' => 'pixel_1',
            'lead_form_ids' => ['form_1'],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Selected Meta Ad Account is not available');

        $this->providers->saveAssetConfiguration($provider->fresh(), [
            'business_id' => 'biz_1',
            'ad_account_id' => 'act_999',
            'page_id' => 'page_1',
            'pixel_id' => 'pixel_1',
            'lead_form_ids' => ['form_1'],
        ]);
    }

    public function test_invalid_selection_does_not_corrupt_existing_configuration(): void
    {
        $this->fakeDiscoveryGraph();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);

        $this->providers->saveAssetConfiguration($provider, [
            'business_id' => 'biz_1',
            'ad_account_id' => 'act_111',
            'page_id' => 'page_1',
            'pixel_id' => 'pixel_1',
            'lead_form_ids' => ['form_1'],
        ]);

        try {
            $this->providers->saveAssetConfiguration($provider->fresh(), [
                'business_id' => 'biz_1',
                'ad_account_id' => 'act_111',
                'page_id' => 'page_1',
                'pixel_id' => 'pixel_1',
                'lead_form_ids' => ['form_missing'],
            ]);
            $this->fail('Expected invalid form selection to throw.');
        } catch (\InvalidArgumentException) {
            // expected
        }

        $this->assertSame(['form_1'], $provider->fresh()->credential->configuration['lead_form_ids']);
        $this->assertSame('meta-access-token', $provider->fresh()->credential->access_token);
    }

    public function test_expired_token_marks_expired_without_clearing_configuration(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/me/businesses*' => Http::response([
                'error' => [
                    'message' => 'Error validating access token: Session has expired',
                    'code' => 190,
                ],
            ], 400),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);

        $this->providers->updateCredentialConfiguration($provider, [
            'business_id' => 'biz_1',
            'ad_account_id' => 'act_111',
            'page_id' => 'page_1',
            'pixel_id' => 'pixel_1',
            'lead_form_ids' => ['form_1'],
        ]);

        $result = $this->providers->discoverAssets($provider->fresh());

        $this->assertFalse($result['ok']);
        $this->assertSame(MarketingProvider::STATUS_EXPIRED, $provider->fresh()->status);
        $this->assertSame('biz_1', $provider->fresh()->credential->configuration['business_id']);
        $this->assertSame('meta-access-token', $provider->fresh()->credential->access_token);
    }

    public function test_tenant_isolation_for_asset_configuration(): void
    {
        $this->fakeDiscoveryGraph();

        [, $orgA] = $this->setupUserWithOrg();
        [, $orgB] = $this->setupUserWithOrg();

        app(TenantContext::class)->set($orgA);
        $providerA = $this->connectMeta($orgA, 'token-a');
        $this->providers->saveAssetConfiguration($providerA, [
            'business_id' => 'biz_1',
            'ad_account_id' => 'act_111',
            'page_id' => 'page_1',
            'pixel_id' => 'pixel_1',
            'lead_form_ids' => ['form_1'],
        ]);

        app(TenantContext::class)->set($orgB);
        $providerB = $this->connectMeta($orgB, 'token-b');
        $this->providers->saveAssetConfiguration($providerB, [
            'business_id' => 'biz_1',
            'ad_account_id' => 'act_111',
            'page_id' => 'page_1',
            'pixel_id' => null,
            'lead_form_ids' => ['form_2'],
        ]);

        app(TenantContext::class)->set($orgA);
        $this->assertSame(['form_1'], $providerA->fresh()->credential->configuration['lead_form_ids']);

        app(TenantContext::class)->set($orgB);
        $this->assertSame(['form_2'], $providerB->fresh()->credential->configuration['lead_form_ids']);
        $this->assertNull(
            $this->providers->findProviderForOrganization($orgB, $providerA->id)
        );
    }

    public function test_integration_ui_shows_assets_and_saves_selection(): void
    {
        $this->fakeDiscoveryGraph();

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('integrations.show', ['provider' => 'meta']))
            ->assertOk()
            ->assertSee('Business assets')
            ->assertSee('Acme Business')
            ->assertSee('Acme Ads')
            ->assertSee('Acme Page')
            ->assertSee('Acme Pixel')
            ->assertSee('Summer Lead Form')
            ->assertSee('Refresh Assets')
            ->assertDontSee('meta-access-token');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('integrations.assets.save', ['provider' => 'meta']), [
                'business_id' => 'biz_1',
                'ad_account_id' => 'act_111',
                'page_id' => 'page_1',
                'pixel_id' => 'pixel_1',
                'lead_form_ids' => ['form_1', 'form_2'],
            ])
            ->assertRedirect(route('integrations.show', ['provider' => 'meta']));

        $provider = $this->providers->findProvider($organization, 'meta');
        $this->assertSame('biz_1', $provider->credential->configuration['business_id']);
        $this->assertSame(['form_1', 'form_2'], $provider->credential->configuration['lead_form_ids']);
    }

    public function test_refresh_assets_requeries_meta(): void
    {
        $this->fakeDiscoveryGraph();

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('integrations.assets.refresh', ['provider' => 'meta']))
            ->assertRedirect(route('integrations.show', ['provider' => 'meta']))
            ->assertSessionHas('status', 'integration-assets-refreshed');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/me/businesses');
        });
    }

    public function test_selected_assets_remain_selected_after_refresh_when_still_available(): void
    {
        $this->fakeDiscoveryGraph();

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);

        $this->providers->saveAssetConfiguration($provider, [
            'business_id' => 'biz_1',
            'ad_account_id' => 'act_111',
            'page_id' => 'page_1',
            'pixel_id' => 'pixel_1',
            'lead_form_ids' => ['form_1'],
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('integrations.show', ['provider' => 'meta']))
            ->assertOk()
            ->assertSee('value="biz_1"', false)
            ->assertSee('selected', false)
            ->assertSee('value="form_1"', false);
    }

    public function test_marketing_platform_untouched_by_asset_discovery(): void
    {
        $this->fakeDiscoveryGraph();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $before = MarketingVisitor::query()->count();
        $provider = $this->connectMeta($organization);
        $this->providers->discoverAssets($provider);
        $this->providers->saveAssetConfiguration($provider->fresh(), [
            'business_id' => 'biz_1',
            'ad_account_id' => 'act_111',
            'page_id' => 'page_1',
            'pixel_id' => 'pixel_1',
            'lead_form_ids' => ['form_1'],
        ]);

        $this->assertSame($before, MarketingVisitor::query()->count());
        $this->assertInstanceOf(MarketingTrackingService::class, app(MarketingTrackingService::class));
    }

    public function test_cross_tenant_cannot_save_assets_for_another_org_provider(): void
    {
        $this->fakeDiscoveryGraph();

        [$userA, $orgA] = $this->setupUserWithOrg();
        [, $orgB] = $this->setupUserWithOrg();

        app(TenantContext::class)->set($orgA);
        $this->connectMeta($orgA);

        app(TenantContext::class)->set($orgB);
        $this->connectMeta($orgB, 'token-b');

        $this->actingAs($userA)
            ->withSession(['current_organization_id' => $orgA->id])
            ->post(route('integrations.assets.save', ['provider' => 'meta']), [
                'business_id' => 'biz_1',
                'ad_account_id' => 'act_111',
                'page_id' => 'page_1',
                'pixel_id' => 'pixel_1',
                'lead_form_ids' => ['form_1'],
            ])
            ->assertRedirect();

        $this->assertSame(
            ['form_1'],
            $this->providers->findProvider($orgA, 'meta')->credential->configuration['lead_form_ids']
        );
        $this->assertSame(
            [],
            $this->providers->findProvider($orgB, 'meta')->credential->configuration ?? []
        );
    }
}
