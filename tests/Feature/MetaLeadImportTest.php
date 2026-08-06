<?php

namespace Tests\Feature;

use App\Events\MarketingLeadImported;
use App\Models\Lead;
use App\Models\MarketingProvider;
use App\Models\MarketingProviderImportedLead;
use App\Models\MarketingProviderLeadImportRun;
use App\Models\MarketingVisitor;
use App\Models\Organization;
use App\Models\User;
use App\Services\Marketing\Providers\MarketingProviderRegistry;
use App\Services\MarketingProviderService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaLeadImportTest extends TestCase
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
        array $formIds = ['form_1'],
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
                'lead_form_ids' => $formIds,
            ],
        ]);

        return $provider->fresh(['credential']);
    }

    protected function fakeLeadEntries(bool $emptySecondPass = false): void
    {
        $callCount = 0;

        Http::fake(function (Request $request) use (&$callCount, $emptySecondPass) {
            $url = $request->url();

            if (str_contains($url, '/form_1/leads')) {
                $callCount++;

                if ($emptySecondPass && $callCount > 1) {
                    return Http::response(['data' => []]);
                }

                return Http::response([
                    'data' => [
                        [
                            'id' => 'meta_lead_1',
                            'created_time' => '2026-07-10T10:00:00+0000',
                            'ad_id' => 'ad_9',
                            'ad_name' => 'Summer Ad',
                            'form_id' => 'form_1',
                            'field_data' => [
                                ['name' => 'full_name', 'values' => ['Jane Doe']],
                                ['name' => 'email', 'values' => ['jane@example.com']],
                                ['name' => 'phone_number', 'values' => ['5551112222']],
                                ['name' => 'city', 'values' => ['Austin']],
                            ],
                        ],
                        [
                            'id' => 'meta_lead_2',
                            'created_time' => '2026-07-11T10:00:00+0000',
                            'form_id' => 'form_1',
                            'field_data' => [
                                ['name' => 'email', 'values' => ['bob@example.com']],
                                ['name' => 'phone_number', 'values' => ['5553334444']],
                            ],
                        ],
                    ],
                ]);
            }

            return Http::response(['data' => []]);
        });
    }

    public function test_meta_declares_lead_import_capability(): void
    {
        $meta = app(MarketingProviderRegistry::class)->resolve('meta');

        $this->assertContains('lead_import', $meta->capabilities());
        $this->assertTrue($this->providers->supportsLeadImport(
            new MarketingProvider(['slug' => 'meta'])
        ));
    }

    public function test_first_import_creates_leads_through_lead_service(): void
    {
        $this->fakeLeadEntries();

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);

        Event::fake([MarketingLeadImported::class]);
        $transactionManager = app('db.transactions');
        app()->offsetUnset('db.transactions');
        try {
            $result = $this->providers->importLeadEntries($provider, $user);
        } finally {
            app()->instance('db.transactions', $transactionManager);
        }

        $this->assertTrue($result['ok']);
        $this->assertSame(2, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame(2, Lead::query()->count());
        $this->assertSame(2, MarketingProviderImportedLead::query()->count());

        $jane = Lead::query()->where('email', 'jane@example.com')->first();
        $this->assertNotNull($jane);
        $this->assertSame('Jane Doe', $jane->name);
        $this->assertSame('5551112222', $jane->phone);
        $this->assertSame('facebook', $jane->source);
        $this->assertSame('new', $jane->status);
        $this->assertSame($user->id, $jane->created_by);
        $this->assertSame('Austin', $jane->custom_fields['provider']['unmapped_fields']['city'] ?? null);
        $this->assertSame('meta_lead_1', $jane->custom_fields['provider']['external_lead_id'] ?? null);

        $bob = Lead::query()->where('email', 'bob@example.com')->first();
        $this->assertSame('bob', $bob->name);

        $run = MarketingProviderLeadImportRun::query()->first();
        $this->assertNotNull($run);
        $this->assertSame(2, $run->imported_count);
        $this->assertSame(0, $run->skipped_count);
        $this->assertSame($user->id, $run->triggered_by);
        Event::assertDispatchedTimes(MarketingLeadImported::class, 2);
        Event::assertDispatched(MarketingLeadImported::class, fn (MarketingLeadImported $event): bool => $event->organizationId === $organization->id
            && $event->subjectId === $jane->id
            && (int) $event->payload['actor_id'] === $user->id
            && $event->payload['marketing_provider_id'] === $provider->id
            && $event->payload['external_lead_id'] === 'meta_lead_1');
    }

    public function test_repeat_import_skips_duplicates(): void
    {
        $this->fakeLeadEntries();

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);

        $this->providers->importLeadEntries($provider, $user);
        $this->assertSame(2, Lead::query()->count());

        $second = $this->providers->importLeadEntries($provider->fresh(), $user);

        $this->assertTrue($second['ok']);
        $this->assertSame(0, $second['imported']);
        $this->assertSame(2, $second['skipped']);
        $this->assertSame(2, Lead::query()->count());
        $this->assertSame(2, MarketingProviderImportedLead::query()->count());
        $this->assertSame(2, MarketingProviderLeadImportRun::query()->count());
    }

    public function test_malformed_entry_is_counted_as_failed_without_stopping_others(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/form_1/leads')) {
                return Http::response([
                    'data' => [
                        [
                            'id' => 'meta_lead_ok',
                            'created_time' => '2026-07-10T10:00:00+0000',
                            'form_id' => 'form_1',
                            'field_data' => [
                                ['name' => 'full_name', 'values' => ['Valid Person']],
                                ['name' => 'email', 'values' => ['valid@example.com']],
                            ],
                        ],
                        [
                            // Missing id — adapter marks fetch_ok false
                            'created_time' => '2026-07-10T11:00:00+0000',
                            'form_id' => 'form_1',
                            'field_data' => [
                                ['name' => 'email', 'values' => ['bad@example.com']],
                            ],
                        ],
                    ],
                ]);
            }

            return Http::response(['data' => []]);
        });

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);

        $result = $this->providers->importLeadEntries($provider, $user);

        $this->assertSame(1, $result['imported']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame(1, Lead::query()->count());
        $this->assertSame(MarketingProviderLeadImportRun::STATUS_PARTIAL, $result['status']);
    }

    public function test_expired_credentials_do_not_import(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/leads')) {
                return Http::response([
                    'error' => [
                        'message' => 'Error validating access token: Session has expired',
                        'code' => 190,
                    ],
                ], 400);
            }

            return Http::response(['data' => []]);
        });

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);

        $result = $this->providers->importLeadEntries($provider, $user);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, Lead::query()->count());
        $this->assertSame(MarketingProvider::STATUS_EXPIRED, $provider->fresh()->status);
        $this->assertSame(1, MarketingProviderLeadImportRun::query()->count());
    }

    public function test_tenant_isolation_for_imported_leads(): void
    {
        $this->fakeLeadEntries();

        [$userA, $orgA] = $this->setupUserWithOrg();
        [$userB, $orgB] = $this->setupUserWithOrg();

        app(TenantContext::class)->set($orgA);
        $providerA = $this->connectMeta($orgA, ['form_1'], 'token-a');
        $this->providers->importLeadEntries($providerA, $userA);

        app(TenantContext::class)->set($orgB);
        $providerB = $this->connectMeta($orgB, ['form_1'], 'token-b');
        $this->providers->importLeadEntries($providerB, $userB);

        app(TenantContext::class)->set($orgA);
        $this->assertSame(2, Lead::query()->where('organization_id', $orgA->id)->count());
        $this->assertSame(2, MarketingProviderImportedLead::query()->where('organization_id', $orgA->id)->count());

        app(TenantContext::class)->set($orgB);
        $this->assertSame(2, Lead::query()->where('organization_id', $orgB->id)->count());
        $this->assertNull($this->providers->findProviderForOrganization($orgB, $providerA->id));
    }

    public function test_ui_import_leads_and_shows_stats(): void
    {
        $this->fakeLeadEntries();

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMeta($organization);

        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, '/me/businesses') || str_contains($url, '/me/accounts')) {
                return Http::response(['data' => []]);
            }

            if (str_contains($url, '/form_1/leads')) {
                return Http::response([
                    'data' => [
                        [
                            'id' => 'meta_lead_1',
                            'created_time' => '2026-07-10T10:00:00+0000',
                            'form_id' => 'form_1',
                            'field_data' => [
                                ['name' => 'full_name', 'values' => ['Jane Doe']],
                                ['name' => 'email', 'values' => ['jane@example.com']],
                            ],
                        ],
                    ],
                ]);
            }

            return Http::response(['data' => []]);
        });

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('integrations.leads.import', ['provider' => 'meta']))
            ->assertRedirect(route('integrations.show', ['provider' => 'meta']))
            ->assertSessionHas('status', 'integration-leads-imported');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('integrations.show', ['provider' => 'meta']))
            ->assertOk()
            ->assertSee('Import Leads')
            ->assertSee('Imported')
            ->assertSee('1')
            ->assertDontSee('meta-access-token');
    }

    public function test_marketing_platform_not_bypassed_and_no_visitor_still_creates_lead(): void
    {
        $this->fakeLeadEntries();

        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMeta($organization);

        $beforeVisitors = MarketingVisitor::query()->count();
        $this->providers->importLeadEntries($provider, $user);

        $this->assertSame($beforeVisitors, MarketingVisitor::query()->count());
        $this->assertSame(2, Lead::query()->count());
        $this->assertDatabaseCount('marketing_conversions', 0);
    }
}
