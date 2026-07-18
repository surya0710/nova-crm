<?php

namespace Tests\Feature;

use App\Models\MarketingProvider;
use App\Models\MarketingVisitor;
use App\Models\Organization;
use App\Models\User;
use App\Services\Marketing\Providers\GoogleAdsProvider;
use App\Services\Marketing\Providers\MarketingProviderRegistry;
use App\Services\MarketingProviderService;
use App\Services\MarketingTrackingService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAdsAssetDiscoveryTest extends TestCase
{
    use RefreshDatabase;

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
            'marketing.providers.google_ads.api_base_url' => 'https://googleads.googleapis.com',
            'marketing.providers.google_ads.token_url' => 'https://oauth2.googleapis.com/token',
            'marketing.providers.google_ads.token_info_url' => 'https://oauth2.googleapis.com/tokeninfo',
            'marketing.providers.google_ads.revoke_url' => 'https://oauth2.googleapis.com/revoke',
            'marketing.providers.google_ads.scopes' => [
                'https://www.googleapis.com/auth/adwords',
                'openid',
                'email',
            ],
        ]);

        $this->providers = app(MarketingProviderService::class);
        $this->google = app(MarketingProviderRegistry::class)->resolve('google_ads');
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

    protected function connectGoogle(Organization $organization, string $token = 'google-access-token'): MarketingProvider
    {
        $provider = $this->providers->registerProvider($organization, 'google_ads');

        $this->providers->storeCredentials($provider, [
            'access_token' => $token,
            'refresh_token' => 'google-refresh-token',
            'token_type' => 'Bearer',
            'expires_at' => now()->addHour(),
            'external_account_id' => 'google-user-1',
            'configuration' => [],
        ]);

        return $provider->fresh(['credential']);
    }

    /**
     * Fakes two accessible customers: a regular client account with paginated
     * conversion actions and a manager account without conversion actions.
     */
    protected function fakeDiscoveryApi(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, 'customers:listAccessibleCustomers')) {
                return Http::response([
                    'resourceNames' => ['customers/1112223333', 'customers/8885550000'],
                ]);
            }

            if (str_contains($url, 'customers/1112223333/googleAds:search')) {
                $body = $request->data();
                $query = (string) ($body['query'] ?? '');

                if (str_contains($query, 'FROM customer')) {
                    return Http::response([
                        'results' => [[
                            'customer' => [
                                'id' => '1112223333',
                                'descriptiveName' => 'Nova Motors',
                                'currencyCode' => 'USD',
                                'timeZone' => 'Asia/Kolkata',
                                'manager' => false,
                            ],
                        ]],
                    ]);
                }

                if (($body['pageToken'] ?? null) === 'page-2') {
                    return Http::response([
                        'results' => [[
                            'conversionAction' => [
                                'id' => '900002',
                                'name' => 'Lead Submitted',
                                'category' => 'SUBMIT_LEAD_FORM',
                                'type' => 'WEBPAGE',
                                'status' => 'HIDDEN',
                                'primaryForGoal' => false,
                            ],
                        ]],
                    ]);
                }

                return Http::response([
                    'results' => [[
                        'conversionAction' => [
                            'id' => '900001',
                            'name' => 'CRM Purchase',
                            'category' => 'PURCHASE',
                            'type' => 'UPLOAD_CLICKS',
                            'status' => 'ENABLED',
                            'primaryForGoal' => true,
                        ],
                    ]],
                    'nextPageToken' => 'page-2',
                ]);
            }

            if (str_contains($url, 'customers/8885550000/googleAds:search')) {
                return Http::response([
                    'results' => [[
                        'customer' => [
                            'id' => '8885550000',
                            'descriptiveName' => 'Nova Manager',
                            'currencyCode' => 'USD',
                            'timeZone' => 'America/New_York',
                            'manager' => true,
                        ],
                    ]],
                ]);
            }

            return Http::response(['error' => ['message' => 'Unexpected request: '.$url]], 400);
        });
    }

    public function test_google_ads_declares_asset_discovery_capability(): void
    {
        $this->assertContains('asset_discovery', $this->google->capabilities());

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);

        $this->assertTrue($this->providers->supportsAssetDiscovery($provider));
    }

    public function test_discovers_customers_and_paginated_conversion_actions(): void
    {
        $this->fakeDiscoveryApi();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);

        $result = $this->providers->discoverAssets($provider);

        $this->assertTrue($result['ok']);
        $this->assertCount(2, $result['assets']['customers']);

        $client = collect($result['assets']['customers'])->firstWhere('id', '1112223333');
        $this->assertSame('Nova Motors', $client['descriptive_name']);
        $this->assertSame('USD', $client['currency_code']);
        $this->assertSame('Asia/Kolkata', $client['time_zone']);
        $this->assertFalse($client['manager']);

        $manager = collect($result['assets']['customers'])->firstWhere('id', '8885550000');
        $this->assertTrue($manager['manager']);

        // Both pages collected; manager account contributes no conversion actions.
        $this->assertCount(2, $result['assets']['conversion_actions']);
        $ids = array_column($result['assets']['conversion_actions'], 'id');
        $this->assertSame(['900001', '900002'], $ids);

        $purchase = $result['assets']['conversion_actions'][0];
        $this->assertSame('1112223333', $purchase['customer_id']);
        $this->assertSame('CRM Purchase', $purchase['name']);
        $this->assertSame('PURCHASE', $purchase['category']);
        $this->assertSame('UPLOAD_CLICKS', $purchase['type']);
        $this->assertSame('ENABLED', $purchase['status']);
        $this->assertTrue($purchase['primary_for_goal']);
        $this->assertTrue($purchase['active']);

        $hidden = $result['assets']['conversion_actions'][1];
        $this->assertSame('HIDDEN', $hidden['status']);
        $this->assertFalse($hidden['active']);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'googleAds:search')
            && $request->hasHeader('Authorization', 'Bearer google-access-token')
            && $request->hasHeader('developer-token', 'google-developer-token'));
    }

    public function test_discovery_with_no_accessible_customers_returns_empty_assets(): void
    {
        Http::fake([
            'googleads.googleapis.com/v22/customers:listAccessibleCustomers' => Http::response([
                'resourceNames' => [],
            ]),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);

        $result = $this->providers->discoverAssets($provider);

        $this->assertTrue($result['ok']);
        $this->assertSame([], $result['assets']['customers']);
        $this->assertSame([], $result['assets']['conversion_actions']);
        $this->assertSame(MarketingProvider::STATUS_CONNECTED, $provider->fresh()->status);
    }

    public function test_inaccessible_customer_soft_fails_without_aborting_discovery(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, 'customers:listAccessibleCustomers')) {
                return Http::response([
                    'resourceNames' => ['customers/1112223333', 'customers/4440001111'],
                ]);
            }

            if (str_contains($url, 'customers/4440001111/googleAds:search')) {
                return Http::response([
                    'error' => [
                        'code' => 403,
                        'message' => 'The caller does not have permission',
                        'status' => 'PERMISSION_DENIED',
                    ],
                ], 403);
            }

            $query = (string) ($request->data()['query'] ?? '');
            if (str_contains($query, 'FROM customer')) {
                return Http::response([
                    'results' => [[
                        'customer' => [
                            'id' => '1112223333',
                            'descriptiveName' => 'Nova Motors',
                            'currencyCode' => 'USD',
                            'timeZone' => 'Asia/Kolkata',
                            'manager' => false,
                        ],
                    ]],
                ]);
            }

            return Http::response([
                'results' => [[
                    'conversionAction' => [
                        'id' => '900001',
                        'name' => 'CRM Purchase',
                        'category' => 'PURCHASE',
                        'type' => 'UPLOAD_CLICKS',
                        'status' => 'ENABLED',
                        'primaryForGoal' => true,
                    ],
                ]],
            ]);
        });

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);

        $result = $this->providers->discoverAssets($provider);

        $this->assertTrue($result['ok']);
        $this->assertCount(2, $result['assets']['customers']);

        $inaccessible = collect($result['assets']['customers'])->firstWhere('id', '4440001111');
        $this->assertFalse($inaccessible['accessible']);
        $this->assertNull($inaccessible['descriptive_name']);

        $this->assertSame(['900001'], array_column($result['assets']['conversion_actions'], 'id'));

        // Inaccessible accounts cannot be selected as the primary customer.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not accessible');
        $this->providers->saveAssetConfiguration($provider->fresh(), [
            'customer_id' => '4440001111',
            'conversion_action_ids' => [],
        ]);
    }

    public function test_saves_and_updates_selection_in_configuration_json(): void
    {
        $this->fakeDiscoveryApi();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);

        $credential = $this->providers->saveAssetConfiguration($provider, [
            'customer_id' => '1112223333',
            'conversion_action_ids' => ['900001', '900001', '900002'],
        ]);

        $this->assertSame([
            'customer_id' => '1112223333',
            'conversion_action_ids' => ['900001', '900002'],
        ], $credential->configuration);
        $this->assertSame('google-access-token', $provider->fresh()->credential->access_token);

        $updated = $this->providers->saveAssetConfiguration($provider->fresh(), [
            'customer_id' => '1112223333',
            'conversion_action_ids' => ['900002'],
        ]);

        $this->assertSame(['900002'], $updated->configuration['conversion_action_ids']);
        $this->assertSame('google-access-token', $provider->fresh()->credential->access_token);
    }

    public function test_rejects_invalid_selections_without_corrupting_configuration(): void
    {
        $this->fakeDiscoveryApi();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);

        $this->providers->saveAssetConfiguration($provider, [
            'customer_id' => '1112223333',
            'conversion_action_ids' => ['900001'],
        ]);

        try {
            $this->providers->saveAssetConfiguration($provider->fresh(), [
                'customer_id' => '1112223333',
                'conversion_action_ids' => ['999999'],
            ]);
            $this->fail('Expected unknown conversion action selection to throw.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('conversion action', $e->getMessage());
        }

        try {
            $this->providers->saveAssetConfiguration($provider->fresh(), [
                'customer_id' => null,
                'conversion_action_ids' => ['900001'],
            ]);
            $this->fail('Expected conversion actions without a customer account to throw.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('customer account', $e->getMessage());
        }

        $this->assertSame([
            'customer_id' => '1112223333',
            'conversion_action_ids' => ['900001'],
        ], $provider->fresh()->credential->configuration);
        $this->assertSame('google-access-token', $provider->fresh()->credential->access_token);
    }

    public function test_removed_conversion_actions_surface_inactive_and_configuration_is_preserved(): void
    {
        $this->fakeDiscoveryApi();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);

        // '900099' no longer exists remotely; it must appear inactive, never be
        // silently dropped from configuration by discovery or refresh.
        $this->providers->updateCredentialConfiguration($provider, [
            'customer_id' => '1112223333',
            'conversion_action_ids' => ['900001', '900099'],
        ]);

        $result = $this->providers->discoverAssets($provider->fresh());

        $this->assertTrue($result['ok']);
        $removed = collect($result['assets']['conversion_actions'])->firstWhere('id', '900099');
        $this->assertNotNull($removed);
        $this->assertFalse($removed['active']);
        $this->assertTrue($removed['missing']);
        $this->assertSame('REMOVED', $removed['status']);

        $stillAvailable = collect($result['assets']['conversion_actions'])->firstWhere('id', '900001');
        $this->assertTrue($stillAvailable['active']);
        $this->assertFalse($stillAvailable['missing']);

        $this->assertSame(
            ['900001', '900099'],
            $provider->fresh()->credential->configuration['conversion_action_ids']
        );

        // Saving the still-valid subset succeeds; re-selecting the removed action fails.
        $updated = $this->providers->saveAssetConfiguration($provider->fresh(), [
            'customer_id' => '1112223333',
            'conversion_action_ids' => ['900001'],
        ]);
        $this->assertSame(['900001'], $updated->configuration['conversion_action_ids']);

        $this->expectException(\InvalidArgumentException::class);
        $this->providers->saveAssetConfiguration($provider->fresh(), [
            'customer_id' => '1112223333',
            'conversion_action_ids' => ['900099'],
        ]);
    }

    public function test_expired_token_marks_expired_without_clearing_configuration(): void
    {
        Http::fake();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);

        $this->providers->updateCredentialConfiguration($provider, [
            'customer_id' => '1112223333',
            'conversion_action_ids' => ['900001'],
        ]);

        $this->providers->storeCredentials($provider->fresh(), [
            'access_token' => 'google-access-token',
            'refresh_token' => 'google-refresh-token',
            'expires_at' => now()->subMinute(),
            'configuration' => [
                'customer_id' => '1112223333',
                'conversion_action_ids' => ['900001'],
            ],
        ]);

        $result = $this->providers->discoverAssets($provider->fresh(['credential']));

        $this->assertFalse($result['ok']);
        $this->assertSame(MarketingProvider::STATUS_EXPIRED, $provider->fresh()->status);
        $this->assertSame('1112223333', $provider->fresh()->credential->configuration['customer_id']);
        Http::assertNothingSent();
    }

    public function test_revoked_credentials_map_to_expired_status(): void
    {
        Http::fake([
            'googleads.googleapis.com/v22/customers:listAccessibleCustomers' => Http::response([
                'error' => [
                    'code' => 401,
                    'message' => 'Request had invalid authentication credentials.',
                    'status' => 'UNAUTHENTICATED',
                ],
            ], 401),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);

        $result = $this->providers->discoverAssets($provider);

        $this->assertFalse($result['ok']);
        $this->assertSame(MarketingProvider::STATUS_EXPIRED, $provider->fresh()->status);
        $this->assertStringContainsString('invalid authentication credentials', (string) $result['message']);
    }

    public function test_quota_and_malformed_responses_are_normalized_to_error_status(): void
    {
        Http::fake([
            'googleads.googleapis.com/v22/customers:listAccessibleCustomers' => Http::sequence()
                ->push([
                    'error' => [
                        'code' => 429,
                        'message' => 'Resource has been exhausted (e.g. check quota).',
                        'status' => 'RESOURCE_EXHAUSTED',
                    ],
                ], 429)
                ->push('not json', 200),
        ]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);

        $quota = $this->providers->discoverAssets($provider);
        $this->assertFalse($quota['ok']);
        $this->assertSame(MarketingProvider::STATUS_ERROR, $provider->fresh()->status);
        $this->assertStringContainsString('exhausted', (string) $quota['message']);

        $malformed = $this->providers->discoverAssets($provider->fresh());
        $this->assertFalse($malformed['ok']);
        $this->assertStringContainsString('unknown Google API error', (string) $malformed['message']);
    }

    public function test_tenant_isolation_for_google_asset_configuration(): void
    {
        $this->fakeDiscoveryApi();

        [, $orgA] = $this->setupUserWithOrg();
        [, $orgB] = $this->setupUserWithOrg();

        app(TenantContext::class)->set($orgA);
        $providerA = $this->connectGoogle($orgA, 'token-a');
        $this->providers->saveAssetConfiguration($providerA, [
            'customer_id' => '1112223333',
            'conversion_action_ids' => ['900001'],
        ]);

        app(TenantContext::class)->set($orgB);
        $providerB = $this->connectGoogle($orgB, 'token-b');
        $this->providers->saveAssetConfiguration($providerB, [
            'customer_id' => '1112223333',
            'conversion_action_ids' => ['900002'],
        ]);

        app(TenantContext::class)->set($orgA);
        $this->assertSame(['900001'], $providerA->fresh()->credential->configuration['conversion_action_ids']);

        app(TenantContext::class)->set($orgB);
        $this->assertSame(['900002'], $providerB->fresh()->credential->configuration['conversion_action_ids']);
        $this->assertNull($this->providers->findProviderForOrganization($orgB, $providerA->id));
    }

    public function test_integration_ui_shows_google_assets_and_saves_selection(): void
    {
        $this->fakeDiscoveryApi();

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectGoogle($organization);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('integrations.show', ['provider' => 'google_ads']))
            ->assertOk()
            ->assertSee('Business assets')
            ->assertSee('Customer Account')
            ->assertSee('Nova Motors')
            ->assertSee('Nova Manager')
            ->assertSee('Conversion Actions')
            ->assertSee('CRM Purchase')
            ->assertSee('Lead Submitted')
            ->assertSee('Refresh Assets')
            ->assertDontSee('Business Manager')
            ->assertDontSee('google-access-token');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('integrations.assets.save', ['provider' => 'google_ads']), [
                'customer_id' => '1112223333',
                'conversion_action_ids' => ['900001', '900002'],
            ])
            ->assertRedirect(route('integrations.show', ['provider' => 'google_ads']))
            ->assertSessionHas('status', 'integration-assets-saved');

        $provider = $this->providers->findProvider($organization, 'google_ads');
        $this->assertSame('1112223333', $provider->credential->configuration['customer_id']);
        $this->assertSame(['900001', '900002'], $provider->credential->configuration['conversion_action_ids']);
    }

    public function test_refresh_assets_requeries_google_and_preserves_selection(): void
    {
        $this->fakeDiscoveryApi();

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);

        $this->providers->saveAssetConfiguration($provider, [
            'customer_id' => '1112223333',
            'conversion_action_ids' => ['900001'],
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('integrations.assets.refresh', ['provider' => 'google_ads']))
            ->assertRedirect(route('integrations.show', ['provider' => 'google_ads']))
            ->assertSessionHas('status', 'integration-assets-refreshed');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'customers:listAccessibleCustomers'));

        $this->assertSame(
            ['900001'],
            $provider->fresh()->credential->configuration['conversion_action_ids']
        );

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('integrations.show', ['provider' => 'google_ads']))
            ->assertOk()
            ->assertSee('value="1112223333"', false)
            ->assertSee('selected', false)
            ->assertSee('value="900001"', false)
            ->assertSee('checked', false);
    }

    public function test_marketing_platform_untouched_by_google_asset_discovery(): void
    {
        $this->fakeDiscoveryApi();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $before = MarketingVisitor::query()->count();
        $provider = $this->connectGoogle($organization);
        $this->providers->discoverAssets($provider);
        $this->providers->saveAssetConfiguration($provider->fresh(), [
            'customer_id' => '1112223333',
            'conversion_action_ids' => ['900001'],
        ]);

        $this->assertSame($before, MarketingVisitor::query()->count());
        $this->assertInstanceOf(MarketingTrackingService::class, app(MarketingTrackingService::class));
    }
}
