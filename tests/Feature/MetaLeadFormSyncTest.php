<?php

namespace Tests\Feature;

use App\Models\MarketingProvider;
use App\Models\MarketingProviderLeadForm;
use App\Models\MarketingVisitor;
use App\Models\Organization;
use App\Models\User;
use App\Services\Marketing\Providers\MarketingProviderRegistry;
use App\Services\MarketingProviderService;
use App\Services\MarketingTrackingService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaLeadFormSyncTest extends TestCase
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

    protected function connectMetaWithForms(
        Organization $organization,
        array $formIds = ['form_1', 'form_2'],
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
                'ad_account_id' => 'act_111',
                'page_id' => 'page_1',
                'pixel_id' => 'pixel_1',
                'lead_form_ids' => $formIds,
            ],
        ]);

        return $provider->fresh(['credential']);
    }

    protected function fakeFormGraph(?callable $resolver = null): void
    {
        Http::fake(function (Request $request) use ($resolver) {
            if ($resolver !== null) {
                $custom = $resolver($request);
                if ($custom !== null) {
                    return $custom;
                }
            }

            $url = $request->url();

            if (str_contains($url, '/form_1')) {
                return Http::response([
                    'id' => 'form_1',
                    'name' => 'Summer Lead Form',
                    'status' => 'ACTIVE',
                    'locale' => 'en_US',
                    'updated_time' => '2026-07-01T12:00:00+0000',
                    'questions' => [
                        ['id' => 'q1', 'key' => 'full_name', 'label' => 'Full Name', 'type' => 'FULL_NAME'],
                        ['id' => 'q2', 'key' => 'email', 'label' => 'Email', 'type' => 'EMAIL'],
                        ['id' => 'q3', 'key' => 'phone_number', 'label' => 'Phone', 'type' => 'PHONE'],
                    ],
                ]);
            }

            if (str_contains($url, '/form_2')) {
                return Http::response([
                    'id' => 'form_2',
                    'name' => 'Winter Lead Form',
                    'status' => 'ACTIVE',
                    'locale' => 'en_GB',
                    'updated_time' => '2026-07-02T12:00:00+0000',
                    'questions' => [
                        ['id' => 'q4', 'key' => 'email', 'label' => 'Email', 'type' => 'EMAIL'],
                    ],
                ]);
            }

            return Http::response(['data' => []]);
        });
    }

    public function test_meta_supports_lead_form_sync_capability(): void
    {
        $meta = app(MarketingProviderRegistry::class)->resolve('meta');

        $this->assertContains('lead_form_sync', $meta->capabilities());
        $this->assertTrue($this->providers->supportsLeadFormSync(
            new MarketingProvider(['slug' => 'meta'])
        ));
    }

    public function test_first_sync_creates_catalog_rows_with_questions(): void
    {
        $this->fakeFormGraph();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMetaWithForms($organization);

        $result = $this->providers->synchronizeLeadForms($provider);

        $this->assertTrue($result['ok']);
        $this->assertSame(2, $result['synced']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame(2, MarketingProviderLeadForm::query()->count());

        $form = MarketingProviderLeadForm::query()->where('external_form_id', 'form_1')->first();
        $this->assertNotNull($form);
        $this->assertSame('Summer Lead Form', $form->name);
        $this->assertSame(MarketingProviderLeadForm::STATUS_ACTIVE, $form->status);
        $this->assertSame('en_US', $form->locale);
        $this->assertSame('page_1', $form->external_page_id);
        $this->assertSame(3, $form->questionCount());
        $this->assertSame('Full Name', $form->questions[0]['label']);
        $this->assertSame('ACTIVE', $form->providerStatus());
        $this->assertNotNull($form->last_synced_at);
        $this->assertNotNull($provider->fresh()->last_synced_at);
    }

    public function test_incremental_sync_is_idempotent_and_updates_metadata(): void
    {
        $form1Name = 'Summer Lead Form';
        $form1Questions = [
            ['id' => 'q1', 'key' => 'full_name', 'label' => 'Full Name', 'type' => 'FULL_NAME'],
            ['id' => 'q2', 'key' => 'email', 'label' => 'Email', 'type' => 'EMAIL'],
            ['id' => 'q3', 'key' => 'phone_number', 'label' => 'Phone', 'type' => 'PHONE'],
        ];

        Http::fake(function (Request $request) use (&$form1Name, &$form1Questions) {
            $url = $request->url();

            if (str_contains($url, '/form_1')) {
                return Http::response([
                    'id' => 'form_1',
                    'name' => $form1Name,
                    'status' => 'ACTIVE',
                    'locale' => 'en_US',
                    'questions' => $form1Questions,
                ]);
            }

            if (str_contains($url, '/form_2')) {
                return Http::response([
                    'id' => 'form_2',
                    'name' => 'Winter Lead Form',
                    'status' => 'ACTIVE',
                    'locale' => 'en_GB',
                    'questions' => [
                        ['id' => 'q4', 'key' => 'email', 'label' => 'Email', 'type' => 'EMAIL'],
                    ],
                ]);
            }

            return Http::response(['data' => []]);
        });

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMetaWithForms($organization);

        $this->providers->synchronizeLeadForms($provider);
        $this->assertSame(2, MarketingProviderLeadForm::query()->count());

        $form1Name = 'Summer Lead Form Updated';
        $form1Questions = [
            ['id' => 'q1', 'key' => 'full_name', 'label' => 'Full Name', 'type' => 'FULL_NAME'],
            ['id' => 'q2', 'key' => 'email', 'label' => 'Email', 'type' => 'EMAIL'],
            ['id' => 'q3', 'key' => 'phone_number', 'label' => 'Phone', 'type' => 'PHONE'],
            ['id' => 'q5', 'key' => 'city', 'label' => 'City', 'type' => 'CUSTOM'],
        ];

        $this->providers->synchronizeLeadForms($provider->fresh());

        $this->assertSame(2, MarketingProviderLeadForm::query()->count());
        $form = MarketingProviderLeadForm::query()->where('external_form_id', 'form_1')->first();
        $this->assertSame('Summer Lead Form Updated', $form->name);
        $this->assertSame(4, $form->questionCount());
    }

    public function test_deleted_form_is_marked_inactive_not_deleted(): void
    {
        $form2Missing = false;

        Http::fake(function (Request $request) use (&$form2Missing) {
            $url = $request->url();

            if (str_contains($url, '/form_2')) {
                if ($form2Missing) {
                    return Http::response([
                        'error' => [
                            'message' => 'Unsupported get request. Object with ID form_2 does not exist',
                            'code' => 100,
                        ],
                    ], 400);
                }

                return Http::response([
                    'id' => 'form_2',
                    'name' => 'Winter Lead Form',
                    'status' => 'ACTIVE',
                    'locale' => 'en_GB',
                    'questions' => [
                        ['id' => 'q4', 'key' => 'email', 'label' => 'Email', 'type' => 'EMAIL'],
                    ],
                ]);
            }

            if (str_contains($url, '/form_1')) {
                return Http::response([
                    'id' => 'form_1',
                    'name' => 'Summer Lead Form',
                    'status' => 'ACTIVE',
                    'locale' => 'en_US',
                    'questions' => [
                        ['id' => 'q1', 'key' => 'email', 'label' => 'Email', 'type' => 'EMAIL'],
                    ],
                ]);
            }

            return Http::response(['data' => []]);
        });

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMetaWithForms($organization, ['form_1', 'form_2']);
        $this->providers->synchronizeLeadForms($provider);

        $form2Missing = true;
        $result = $this->providers->synchronizeLeadForms($provider->fresh());

        $this->assertSame(1, $result['synced']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame(2, MarketingProviderLeadForm::query()->count());

        $deleted = MarketingProviderLeadForm::query()->where('external_form_id', 'form_2')->first();
        $this->assertSame(MarketingProviderLeadForm::STATUS_INACTIVE, $deleted->status);
        $this->assertSame('Winter Lead Form', $deleted->name);
    }

    public function test_deselected_form_is_marked_inactive(): void
    {
        $this->fakeFormGraph();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMetaWithForms($organization, ['form_1', 'form_2']);
        $this->providers->synchronizeLeadForms($provider);

        $this->providers->updateCredentialConfiguration($provider->fresh(), [
            'business_id' => 'biz_1',
            'ad_account_id' => 'act_111',
            'page_id' => 'page_1',
            'pixel_id' => 'pixel_1',
            'lead_form_ids' => ['form_1'],
        ]);

        $this->fakeFormGraph();

        $this->providers->synchronizeLeadForms($provider->fresh());

        $this->assertSame(
            MarketingProviderLeadForm::STATUS_ACTIVE,
            MarketingProviderLeadForm::query()->where('external_form_id', 'form_1')->value('status')
        );
        $this->assertSame(
            MarketingProviderLeadForm::STATUS_INACTIVE,
            MarketingProviderLeadForm::query()->where('external_form_id', 'form_2')->value('status')
        );
    }

    public function test_expired_credentials_do_not_create_forms(): void
    {
        $this->fakeFormGraph(function (Request $request) {
            if (str_contains($request->url(), '/form_')) {
                return Http::response([
                    'error' => [
                        'message' => 'Error validating access token: Session has expired',
                        'code' => 190,
                    ],
                ], 400);
            }

            return null;
        });

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMetaWithForms($organization, ['form_1']);

        $result = $this->providers->synchronizeLeadForms($provider);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, MarketingProviderLeadForm::query()->count());
        $this->assertSame(MarketingProvider::STATUS_EXPIRED, $provider->fresh()->status);
    }

    public function test_tenant_isolation_for_lead_form_catalog(): void
    {
        $this->fakeFormGraph();

        [, $orgA] = $this->setupUserWithOrg();
        [, $orgB] = $this->setupUserWithOrg();

        app(TenantContext::class)->set($orgA);
        $providerA = $this->connectMetaWithForms($orgA, ['form_1'], 'token-a');
        $this->providers->synchronizeLeadForms($providerA);

        app(TenantContext::class)->set($orgB);
        $providerB = $this->connectMetaWithForms($orgB, ['form_2'], 'token-b');
        $this->providers->synchronizeLeadForms($providerB);

        app(TenantContext::class)->set($orgA);
        $this->assertSame(1, $this->providers->listLeadForms($providerA->fresh())->count());
        $this->assertSame('form_1', $this->providers->listLeadForms($providerA->fresh())->first()->external_form_id);

        app(TenantContext::class)->set($orgB);
        $this->assertSame(1, $this->providers->listLeadForms($providerB->fresh())->count());
        $this->assertSame('form_2', $this->providers->listLeadForms($providerB->fresh())->first()->external_form_id);
        $this->assertNull($this->providers->findProviderForOrganization($orgB, $providerA->id));
    }

    public function test_ui_lists_forms_and_synchronize_action(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->connectMetaWithForms($organization);

        $this->fakeFormGraph(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, '/me/businesses') || str_contains($url, '/me/accounts')) {
                return Http::response(['data' => []]);
            }

            return null;
        });

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('integrations.lead-forms.sync', ['provider' => 'meta']))
            ->assertRedirect(route('integrations.show', ['provider' => 'meta']))
            ->assertSessionHas('status', 'integration-lead-forms-synced');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('integrations.show', ['provider' => 'meta']))
            ->assertOk()
            ->assertSee('Synchronize Forms')
            ->assertSee('Summer Lead Form')
            ->assertSee('Winter Lead Form')
            ->assertSee('en_US')
            ->assertDontSee('meta-access-token')
            ->assertDontSee('"key":"email"');
    }

    public function test_marketing_platform_untouched_by_lead_form_sync(): void
    {
        $this->fakeFormGraph();

        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $before = MarketingVisitor::query()->count();
        $provider = $this->connectMetaWithForms($organization);
        $this->providers->synchronizeLeadForms($provider);

        $this->assertSame($before, MarketingVisitor::query()->count());
        $this->assertInstanceOf(MarketingTrackingService::class, app(MarketingTrackingService::class));
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_sync_without_selected_forms_is_noop(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $provider = $this->connectMetaWithForms($organization, []);

        $result = $this->providers->synchronizeLeadForms($provider);

        $this->assertTrue($result['ok']);
        $this->assertSame(0, $result['synced']);
        $this->assertSame(0, MarketingProviderLeadForm::query()->count());
    }
}
