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
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAdsOfflineConversionUploadTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingProviderService $providers;

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

    /**
     * @param  list<string>  $actionIds
     */
    protected function connectGoogle(
        Organization $organization,
        array $actionIds = ['900001'],
        string $customerId = '1112223333',
        string $token = 'google-access-token',
    ): MarketingProvider {
        $provider = $this->providers->registerProvider($organization, 'google_ads');

        $this->providers->storeCredentials($provider, [
            'access_token' => $token,
            'refresh_token' => 'google-refresh-token',
            'token_type' => 'Bearer',
            'expires_at' => now()->addHour(),
            'external_account_id' => 'google-user-1',
            'configuration' => [
                'customer_id' => $customerId,
                'conversion_action_ids' => $actionIds,
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
        ?string $email = 'jane@example.com',
        ?string $phone = '+1 (555) 111-2222',
        ?float $value = null,
        ?string $currency = null,
    ): array {
        $visitor = MarketingVisitor::factory()->create();
        $session = MarketingSession::factory()->create([
            'visitor_id' => $visitor->id,
        ]);
        MarketingTouch::factory()->create([
            'session_id' => $session->id,
            'occurred_at' => now()->subDay(),
            'channel' => 'paid_search',
            'source' => 'google',
            'medium' => 'cpc',
            'gclid' => 'google-click-'.$visitor->id,
        ]);

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Jane Doe',
            'email' => $email,
            'phone' => $phone,
            'source' => 'google',
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
            'event_value' => $value,
            'currency' => $currency,
            'occurred_at' => now()->subHour(),
        ]);

        return [$lead, $conversion];
    }

    /**
     * @param  list<array<string, mixed>>  $actions
     * @param  null|callable(Request): mixed  $upload
     */
    protected function fakeGoogleApi(array $actions, ?callable $upload = null): void
    {
        Http::fake(function (Request $request) use ($actions, $upload) {
            $url = $request->url();

            if (str_contains($url, 'customers/1112223333/googleAds:search')) {
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
                    'results' => array_map(
                        fn (array $action) => ['conversionAction' => $action],
                        $actions,
                    ),
                ]);
            }

            if (str_contains($url, 'customers/1112223333:uploadClickConversions')) {
                if ($upload !== null) {
                    return $upload($request);
                }

                return Http::response([
                    'results' => array_map(
                        fn (array $conversion) => [
                            'conversionAction' => $conversion['conversionAction'] ?? null,
                            'conversionDateTime' => $conversion['conversionDateTime'] ?? null,
                            'orderId' => $conversion['orderId'] ?? null,
                        ],
                        $request->data()['conversions'] ?? [],
                    ),
                    'jobId' => '123456789',
                ]);
            }

            return Http::response([
                'error' => ['message' => 'Unexpected request: '.$url],
            ], 400);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function supportedActions(): array
    {
        return [
            [
                'id' => '900001',
                'name' => 'New Lead',
                'category' => 'SUBMIT_LEAD_FORM',
                'type' => 'UPLOAD_CLICKS',
                'status' => 'ENABLED',
                'primaryForGoal' => true,
            ],
            [
                'id' => '900002',
                'name' => 'Qualified Lead',
                'category' => 'CONVERTED_LEAD',
                'type' => 'UPLOAD_CLICKS',
                'status' => 'ENABLED',
                'primaryForGoal' => true,
            ],
            [
                'id' => '900003',
                'name' => 'New Customer',
                'category' => 'SIGNUP',
                'type' => 'UPLOAD_CLICKS',
                'status' => 'ENABLED',
                'primaryForGoal' => true,
            ],
            [
                'id' => '900004',
                'name' => 'Opportunity',
                'category' => 'BEGIN_CHECKOUT',
                'type' => 'UPLOAD_CLICKS',
                'status' => 'ENABLED',
                'primaryForGoal' => true,
            ],
            [
                'id' => '900005',
                'name' => 'Won Opportunity',
                'category' => 'PURCHASE',
                'type' => 'UPLOAD_CLICKS',
                'status' => 'ENABLED',
                'primaryForGoal' => true,
            ],
        ];
    }

    public function test_google_ads_declares_offline_conversion_capability(): void
    {
        $google = app(MarketingProviderRegistry::class)->resolve('google_ads');

        $this->assertContains('offline_conversions', $google->capabilities());
        $this->assertTrue($this->providers->supportsOfflineConversions(
            new MarketingProvider(['slug' => 'google_ads'])
        ));
    }

    public function test_successful_upload_maps_all_supported_events_and_records_runtime_history(): void
    {
        $actions = $this->supportedActions();
        $this->fakeGoogleApi($actions);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization, array_column($actions, 'id'));

        $events = [
            MarketingConversion::LEAD_CREATED,
            MarketingConversion::LEAD_CONVERTED,
            MarketingConversion::CUSTOMER_CREATED,
            MarketingConversion::OPPORTUNITY_CREATED,
            MarketingConversion::OPPORTUNITY_WON,
        ];
        $conversionIds = [];

        foreach ($events as $event) {
            [, $conversion] = $this->createAttributedConversion(
                $organization,
                $event,
                $event.'@example.com',
                '+1 555 100 2000',
                $event === MarketingConversion::OPPORTUNITY_WON ? 2500.50 : null,
                $event === MarketingConversion::OPPORTUNITY_WON ? 'usd' : null,
            );
            $conversionIds[] = $conversion->id;
        }

        $result = $this->providers->uploadConversions($provider);

        $this->assertTrue($result['ok']);
        $this->assertSame(5, $result['uploaded']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(MarketingProviderSyncRun::STATUS_COMPLETED, $result['status']);

        $this->assertSame(5, MarketingProviderUploadedConversion::query()->count());
        foreach ($conversionIds as $index => $conversionId) {
            $this->assertDatabaseHas('marketing_provider_uploaded_conversions', [
                'organization_id' => $organization->id,
                'marketing_provider_id' => $provider->id,
                'marketing_conversion_id' => $conversionId,
                'provider_event_name' => $actions[$index]['id'],
                'external_event_id' => 'nova_crm_conversion_'.$conversionId,
            ]);
        }

        $run = $this->providers->latestConversionUploadRun($provider);
        $this->assertNotNull($run);
        $this->assertSame(MarketingProviderSyncRun::TYPE_CONVERSION_UPLOAD, $run->sync_type);
        $this->assertSame(MarketingProviderSyncRun::DIRECTION_OUTBOUND, $run->direction);
        $this->assertSame(5, $run->records_processed);
        $this->assertSame(5, $run->records_succeeded);
        $this->assertSame(0, $run->records_failed);
        $this->assertSame('1112223333', $run->metadata['customer_id'] ?? null);

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), ':uploadClickConversions')) {
                return false;
            }

            $payload = $request->data();
            $conversions = $payload['conversions'] ?? [];
            $purchase = $conversions[4] ?? [];

            return $request->hasHeader('Authorization', 'Bearer google-access-token')
                && $request->hasHeader('developer-token', 'google-developer-token')
                && ($payload['partialFailure'] ?? null) === true
                && count($conversions) === 5
                && ($conversions[0]['conversionAction'] ?? null) === 'customers/1112223333/conversionActions/900001'
                && isset($conversions[0]['userIdentifiers'][0]['hashedEmail'])
                && isset($conversions[0]['userIdentifiers'][1]['hashedPhoneNumber'])
                && str_starts_with((string) ($conversions[0]['gclid'] ?? ''), 'google-click-')
                && str_ends_with((string) ($conversions[0]['conversionDateTime'] ?? ''), '+05:30')
                && ($purchase['conversionValue'] ?? null) === 2500.5
                && ($purchase['currencyCode'] ?? null) === 'USD';
        });
    }

    public function test_gclid_only_conversion_uploads_without_email_or_phone(): void
    {
        $this->fakeGoogleApi([$this->supportedActions()[0]]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);
        $this->createAttributedConversion($organization, MarketingConversion::LEAD_CREATED, null, null);

        $result = $this->providers->uploadConversions($provider);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['uploaded']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame(1, MarketingProviderUploadedConversion::query()->count());

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), ':uploadClickConversions')) {
                return false;
            }

            $conversion = $request->data()['conversions'][0] ?? [];

            return str_starts_with((string) ($conversion['gclid'] ?? ''), 'google-click-')
                && ($conversion['userIdentifiers'] ?? []) === [];
        });
    }

    public function test_duplicate_prevention_is_independent_per_provider(): void
    {
        $this->fakeGoogleApi([$this->supportedActions()[0]]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $google = $this->connectGoogle($organization);
        [, $conversion] = $this->createAttributedConversion($organization);

        $meta = $this->providers->registerProvider($organization, 'meta');
        MarketingProviderUploadedConversion::factory()->create([
            'organization_id' => $organization->id,
            'marketing_provider_id' => $meta->id,
            'marketing_conversion_id' => $conversion->id,
        ]);

        $first = $this->providers->uploadConversions($google);
        $second = $this->providers->uploadConversions($google->fresh());

        $this->assertSame(1, $first['uploaded']);
        $this->assertSame(0, $first['skipped']);
        $this->assertSame(0, $second['uploaded']);
        $this->assertSame(1, $second['skipped']);
        $this->assertSame(2, MarketingProviderUploadedConversion::query()->count());
        $this->assertSame(2, MarketingProviderSyncRun::query()
            ->where('marketing_provider_id', $google->id)
            ->where('sync_type', MarketingProviderSyncRun::TYPE_CONVERSION_UPLOAD)
            ->count());
    }

    public function test_invalid_conversion_action_type_fails_without_uploading(): void
    {
        $this->fakeGoogleApi([[
            'id' => '900001',
            'name' => 'Web Conversion',
            'category' => 'SUBMIT_LEAD_FORM',
            'type' => 'WEBPAGE',
            'status' => 'ENABLED',
        ]]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);
        $this->createAttributedConversion($organization);

        $result = $this->providers->uploadConversions($provider);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $result['uploaded']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame(MarketingProviderSyncRun::STATUS_FAILED, $result['status']);
        $this->assertStringContainsString('not an offline click conversion action', $result['message']);
        $this->assertSame(0, MarketingProviderUploadedConversion::query()->count());
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), ':uploadClickConversions'));
    }

    public function test_removed_conversion_action_fails_safely(): void
    {
        $this->fakeGoogleApi([]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization, ['removed-action']);
        $this->createAttributedConversion($organization);

        $result = $this->providers->uploadConversions($provider);

        $this->assertFalse($result['ok']);
        $this->assertSame(1, $result['failed']);
        $this->assertStringContainsString('removed', $result['message']);
        $this->assertSame(MarketingProviderSyncRun::STATUS_FAILED, $result['status']);
        $this->assertSame(0, MarketingProviderUploadedConversion::query()->count());
    }

    public function test_expired_credentials_fail_without_api_calls_or_upload_rows(): void
    {
        Http::fake();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);
        $this->createAttributedConversion($organization);

        $this->providers->storeCredentials($provider, [
            'access_token' => 'expired-google-access',
            'refresh_token' => 'google-refresh-token',
            'expires_at' => now()->subMinute(),
            'configuration' => [
                'customer_id' => '1112223333',
                'conversion_action_ids' => ['900001'],
            ],
        ]);

        $result = $this->providers->uploadConversions($provider->fresh(['credential']));

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $result['uploaded']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame(MarketingProvider::STATUS_EXPIRED, $provider->fresh()->status);
        $this->assertSame(MarketingProviderSyncRun::STATUS_FAILED, $result['status']);
        $this->assertSame(0, MarketingProviderUploadedConversion::query()->count());
        Http::assertNothingSent();
    }

    public function test_partial_google_failures_continue_and_record_partial_status(): void
    {
        $action = $this->supportedActions()[0];
        $this->fakeGoogleApi([$action], function (Request $request) {
            $conversions = $request->data()['conversions'] ?? [];

            return Http::response([
                'results' => [
                    [
                        'conversionAction' => $conversions[0]['conversionAction'] ?? null,
                        'orderId' => $conversions[0]['orderId'] ?? null,
                    ],
                    [],
                ],
                'partialFailureError' => [
                    'code' => 3,
                    'message' => 'Partial failure.',
                    'details' => [[
                        'errors' => [[
                            'message' => 'The conversion action is invalid.',
                            'location' => [
                                'fieldPathElements' => [
                                    ['fieldName' => 'conversions', 'index' => 1],
                                ],
                            ],
                        ]],
                    ]],
                ],
                'jobId' => 'partial-job',
            ]);
        });

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);
        $this->createAttributedConversion($organization, MarketingConversion::LEAD_CREATED, 'one@example.com');
        $this->createAttributedConversion($organization, MarketingConversion::LEAD_CREATED, 'two@example.com');

        $result = $this->providers->uploadConversions($provider);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['uploaded']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame(MarketingProviderSyncRun::STATUS_PARTIAL, $result['status']);
        $this->assertSame(1, MarketingProviderUploadedConversion::query()->count());
        $this->assertStringContainsString('invalid', $result['results'][1]['message']);
    }

    public function test_revoked_credentials_mark_provider_expired(): void
    {
        $action = $this->supportedActions()[0];
        $this->fakeGoogleApi([$action], fn () => Http::response([
            'error' => [
                'code' => 401,
                'message' => 'Request had invalid authentication credentials.',
                'status' => 'UNAUTHENTICATED',
            ],
        ], 401));

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);
        $this->createAttributedConversion($organization);

        $result = $this->providers->uploadConversions($provider);

        $this->assertFalse($result['ok']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame(MarketingProvider::STATUS_EXPIRED, $provider->fresh()->status);
        $this->assertSame(MarketingProviderSyncRun::STATUS_FAILED, $result['status']);
        $this->assertSame(0, MarketingProviderUploadedConversion::query()->count());
    }

    public function test_uploads_are_tenant_isolated(): void
    {
        $this->fakeGoogleApi([$this->supportedActions()[0]]);

        [, $orgA] = $this->setupUserWithOrg();
        [, $orgB] = $this->setupUserWithOrg();

        app(TenantContext::class)->set($orgA);
        $providerA = $this->connectGoogle($orgA, ['900001'], '1112223333', 'token-a');
        $this->createAttributedConversion($orgA, MarketingConversion::LEAD_CREATED, 'a@example.com');
        $this->providers->uploadConversions($providerA);

        app(TenantContext::class)->set($orgB);
        $providerB = $this->connectGoogle($orgB, ['900001'], '1112223333', 'token-b');
        $this->createAttributedConversion($orgB, MarketingConversion::LEAD_CREATED, 'b@example.com');
        $this->providers->uploadConversions($providerB);

        $this->assertSame(1, MarketingProviderUploadedConversion::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $orgA->id)
            ->where('marketing_provider_id', $providerA->id)
            ->count());
        $this->assertSame(1, MarketingProviderUploadedConversion::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $orgB->id)
            ->where('marketing_provider_id', $providerB->id)
            ->count());

        Http::assertSent(fn (Request $request) => str_contains($request->url(), ':uploadClickConversions')
            && (
                $request->hasHeader('Authorization', 'Bearer token-a')
                || $request->hasHeader('Authorization', 'Bearer token-b')
            ));
    }

    public function test_integration_ui_upload_action_and_stats(): void
    {
        $this->fakeGoogleApi([$this->supportedActions()[0]]);

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectGoogle($organization);
        $this->createAttributedConversion($organization);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('integrations.conversions.upload', ['provider' => 'google_ads']))
            ->assertRedirect(route('integrations.show', ['provider' => 'google_ads']));

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('integrations.show', ['provider' => 'google_ads']))
            ->assertOk()
            ->assertSee('Offline Conversions')
            ->assertSee('Upload Conversions')
            ->assertSee('Completed')
            ->assertDontSee('google-access-token');
    }

    public function test_marketing_conversions_remain_immutable_source(): void
    {
        $this->fakeGoogleApi([$this->supportedActions()[0]]);

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectGoogle($organization);
        [, $conversion] = $this->createAttributedConversion($organization);

        $before = MarketingConversion::query()->count();
        $this->providers->uploadConversions($provider);

        $this->assertSame($before, MarketingConversion::query()->count());
        $this->assertSame($conversion->event_name, $conversion->fresh()->event_name);
        $this->assertSame(1, MarketingProviderUploadedConversion::query()->count());
    }
}
