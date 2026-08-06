<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\MarketingAttribution;
use App\Models\MarketingSession;
use App\Models\MarketingTouch;
use App\Models\MarketingVisitor;
use App\Models\Organization;
use App\Models\User;
use App\Services\LeadConversionService;
use App\Services\LeadService;
use App\Services\MarketingAttributionService;
use App\Services\MarketingTrackingService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MarketingAttributionTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingAttributionService $attribution;

    protected MarketingTrackingService $tracking;

    protected LeadService $leads;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attribution = app(MarketingAttributionService::class);
        $this->tracking = app(MarketingTrackingService::class);
        $this->leads = app(LeadService::class);
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
     * @return array{visitor: MarketingVisitor, session: MarketingSession, touch: MarketingTouch}
     */
    protected function createTrackedVisitor(?Organization $ownedBy = null): array
    {
        $visitor = $this->tracking->createVisitor(['ip' => '203.0.113.10']);

        if ($ownedBy) {
            $visitor->update(['organization_id' => $ownedBy->id]);
        }

        $session = $this->tracking->createSession($visitor, [
            'landing_page' => 'https://example.test/lp',
            'referrer' => 'https://google.com/',
        ]);

        $touch = $this->tracking->recordPageView($session, [
            'url' => 'https://example.test/lp?utm_source=google&utm_medium=cpc&utm_campaign=summer',
            'referrer' => 'https://google.com/',
        ]);

        return compact('visitor', 'session', 'touch');
    }

    // ── Attribution ─────────────────────────────────────────────────────

    public function test_visitor_to_lead_creates_first_touch_attribution(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        ['visitor' => $visitor, 'session' => $session] = $this->createTrackedVisitor();

        $lead = $this->leads->create([
            'organization_id' => $organization->id,
            'name' => 'Attributed Lead',
            'source' => 'website',
            'status' => 'new',
            'priority' => 'medium',
            'visitor_uuid' => $visitor->visitor_uuid,
            'session_uuid' => $session->session_uuid,
        ], $user);

        $attribution = $this->attribution->findPrimaryForLead($lead);

        $this->assertNotNull($attribution);
        $this->assertSame($organization->id, $attribution->organization_id);
        $this->assertSame($visitor->id, $attribution->marketing_visitor_id);
        $this->assertSame($session->id, $attribution->marketing_session_id);
        $this->assertSame($lead->id, $attribution->lead_id);
        $this->assertSame(MarketingAttribution::MODEL_FIRST_TOUCH, $attribution->attribution_model);
        $this->assertTrue($attribution->is_primary);
        $this->assertNull($attribution->customer_id);
        $this->assertNull($attribution->opportunity_id);

        // Visitor ownership resolved at attribution time.
        $this->assertSame($organization->id, $visitor->fresh()->organization_id);

        // Marketing metadata was not copied onto the lead.
        $this->assertSame('website', $lead->source);
        $this->assertArrayNotHasKey('utm_source', $lead->getAttributes());
        $this->assertArrayNotHasKey('gclid', $lead->getAttributes());
        $this->assertArrayNotHasKey('channel', $lead->getAttributes());
    }

    public function test_no_visitor_means_no_attribution(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $lead = $this->leads->create([
            'organization_id' => $organization->id,
            'name' => 'Manual Lead',
            'source' => 'manual_entry',
            'status' => 'new',
            'priority' => 'medium',
        ], $user);

        $this->assertNull($this->attribution->findPrimaryForLead($lead));
        $this->assertDatabaseCount('marketing_attributions', 0);
    }

    public function test_unknown_visitor_uuid_means_no_attribution(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $lead = $this->leads->create([
            'organization_id' => $organization->id,
            'name' => 'Unknown Visitor Lead',
            'source' => 'website',
            'status' => 'new',
            'priority' => 'medium',
            'visitor_uuid' => (string) Str::uuid(),
        ], $user);

        $this->assertNull($this->attribution->findPrimaryForLead($lead));
        $this->assertDatabaseCount('marketing_attributions', 0);
    }

    public function test_duplicate_attribution_prevention_for_same_lead(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        ['visitor' => $visitor] = $this->createTrackedVisitor();

        $lead = $this->leads->create([
            'organization_id' => $organization->id,
            'name' => 'Once',
            'source' => 'website',
            'status' => 'new',
            'priority' => 'medium',
            'visitor_uuid' => $visitor->visitor_uuid,
        ], $user);

        $first = $this->attribution->findPrimaryForLead($lead);
        $second = $this->attribution->attributeLead($lead, [
            'visitor_uuid' => $visitor->visitor_uuid,
        ]);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('marketing_attributions', 1);
    }

    public function test_one_visitor_cannot_receive_second_primary_attribution(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        ['visitor' => $visitor] = $this->createTrackedVisitor();

        $this->leads->create([
            'organization_id' => $organization->id,
            'name' => 'First Lead',
            'source' => 'website',
            'status' => 'new',
            'priority' => 'medium',
            'visitor_uuid' => $visitor->visitor_uuid,
        ], $user);

        $secondLead = $this->leads->create([
            'organization_id' => $organization->id,
            'name' => 'Second Lead',
            'source' => 'website',
            'status' => 'new',
            'priority' => 'medium',
            'visitor_uuid' => $visitor->visitor_uuid,
        ], $user);

        $this->assertDatabaseCount('marketing_attributions', 1);
        $this->assertNull($this->attribution->findPrimaryForLead($secondLead));
    }

    public function test_first_touch_uses_earliest_touch_session(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $visitor = $this->tracking->createVisitor();

        $firstSession = $this->tracking->createSession($visitor, [], now()->subDays(2));
        $this->tracking->createTouch($firstSession, [
            'landing_page' => 'https://example.test/first',
        ], now()->subDays(2));

        $laterSession = $this->tracking->createSession($visitor, [], now()->subHour());
        $this->tracking->createTouch($laterSession, [
            'landing_page' => 'https://example.test/later',
        ], now()->subHour());

        $lead = $this->leads->create([
            'organization_id' => $organization->id,
            'name' => 'First Touch Lead',
            'source' => 'website',
            'status' => 'new',
            'priority' => 'medium',
            'visitor_uuid' => $visitor->visitor_uuid,
            'session_uuid' => $laterSession->session_uuid,
        ], $user);

        $attribution = $this->attribution->findPrimaryForLead($lead);

        $this->assertNotNull($attribution);
        $this->assertSame($firstSession->id, $attribution->marketing_session_id);
        $this->assertSame(MarketingAttribution::MODEL_FIRST_TOUCH, $attribution->attribution_model);
    }

    public function test_cookie_signals_attribute_web_lead_creation(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        ['visitor' => $visitor, 'session' => $session] = $this->createTrackedVisitor();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->withCookie(config('marketing.tracking.visitor_cookie'), $visitor->visitor_uuid)
            ->withCookie(config('marketing.tracking.session_cookie'), $session->session_uuid)
            ->post(route('leads.store'), [
                'name' => 'Cookie Attributed Lead',
                'source' => 'website',
                'priority' => 'medium',
                'status' => 'new',
            ]);

        $response->assertRedirect();

        $lead = Lead::query()->where('name', 'Cookie Attributed Lead')->first();
        $this->assertNotNull($lead);
        $this->assertNotNull($this->attribution->findPrimaryForLead($lead));
    }

    public function test_api_lead_creation_accepts_visitor_uuid(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        ['visitor' => $visitor, 'session' => $session] = $this->createTrackedVisitor();

        Sanctum::actingAs($user, ['*']);

        $response = $this->withHeaders([
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/leads', [
            'name' => 'API Attributed Lead',
            'phone' => '+15550100',
            'source' => 'api',
            'visitor_uuid' => $visitor->visitor_uuid,
            'session_uuid' => $session->session_uuid,
        ]);

        $response->assertCreated();

        $lead = Lead::query()->find($response->json('lead_id'));
        $attribution = $this->attribution->findPrimaryForLead($lead);

        $this->assertNotNull($attribution);
        $this->assertSame($visitor->id, $attribution->marketing_visitor_id);
    }

    // ── Lead conversion propagation ─────────────────────────────────────

    public function test_conversion_propagates_attribution_to_customer_and_opportunity(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        ['visitor' => $visitor] = $this->createTrackedVisitor();

        $lead = $this->leads->create([
            'organization_id' => $organization->id,
            'name' => 'Convert Me',
            'email' => 'convert@example.test',
            'phone' => '+15550199',
            'source' => 'website',
            'status' => 'qualified',
            'priority' => 'high',
            'budget' => 10000,
            'visitor_uuid' => $visitor->visitor_uuid,
        ], $user);

        $this->actingAs($user);

        $result = app(LeadConversionService::class)->convert($lead, [
            'name' => 'Convert Me',
            'email' => 'convert@example.test',
            'phone' => '+15550199',
            'create_opportunity' => true,
        ], $user);

        $attribution = $this->attribution->findPrimaryForLead($lead->fresh());

        $this->assertNotNull($attribution);
        $this->assertSame($result['customer']->id, $attribution->customer_id);
        $this->assertSame($result['opportunity']->id, $attribution->opportunity_id);
        $this->assertDatabaseCount('marketing_attributions', 1);

        $this->assertSame($attribution->id, $this->attribution->findForCustomer($result['customer'])->id);
        $this->assertSame($attribution->id, $this->attribution->findForOpportunity($result['opportunity'])->id);

        // No marketing metadata on CRM entities.
        $this->assertArrayNotHasKey('utm_source', $result['customer']->getAttributes());
        $this->assertArrayNotHasKey('channel', $result['opportunity']->getAttributes());
    }

    public function test_conversion_without_attribution_does_nothing(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->actingAs($user);

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'qualified',
            'name' => 'No Attr',
            'email' => 'noattr@example.test',
            'created_by' => $user->id,
        ]);

        app(LeadConversionService::class)->convert($lead, [
            'name' => 'No Attr',
            'email' => 'noattr@example.test',
            'create_opportunity' => true,
        ], $user);

        $this->assertDatabaseCount('marketing_attributions', 0);
        $this->assertDatabaseHas('customers', ['lead_id' => $lead->id]);
        $this->assertDatabaseHas('opportunities', ['lead_id' => $lead->id]);
    }

    // ── Multi-tenancy ───────────────────────────────────────────────────

    public function test_cross_tenant_visitor_cannot_be_attributed(): void
    {
        [$userA, $orgA] = $this->setupUserWithOrg();
        $orgB = Organization::factory()->create();

        ['visitor' => $visitor] = $this->createTrackedVisitor($orgB);

        $lead = $this->leads->create([
            'organization_id' => $orgA->id,
            'name' => 'Cross Tenant Attempt',
            'source' => 'website',
            'status' => 'new',
            'priority' => 'medium',
            'visitor_uuid' => $visitor->visitor_uuid,
        ], $userA);

        $this->assertNull($this->attribution->findPrimaryForLead($lead));
        $this->assertDatabaseCount('marketing_attributions', 0);
        $this->assertSame($orgB->id, $visitor->fresh()->organization_id);
    }

    public function test_attribution_is_organization_scoped(): void
    {
        [$userA, $orgA] = $this->setupUserWithOrg();
        [$userB, $orgB] = $this->setupUserWithOrg();

        ['visitor' => $visitorA] = $this->createTrackedVisitor();
        ['visitor' => $visitorB] = $this->createTrackedVisitor();

        $leadA = $this->leads->create([
            'organization_id' => $orgA->id,
            'name' => 'Org A Lead',
            'source' => 'website',
            'status' => 'new',
            'priority' => 'medium',
            'visitor_uuid' => $visitorA->visitor_uuid,
        ], $userA);

        $leadB = $this->leads->create([
            'organization_id' => $orgB->id,
            'name' => 'Org B Lead',
            'source' => 'website',
            'status' => 'new',
            'priority' => 'medium',
            'visitor_uuid' => $visitorB->visitor_uuid,
        ], $userB);

        $this->assertSame($orgA->id, $this->attribution->findPrimaryForLead($leadA)->organization_id);
        $this->assertSame($orgB->id, $this->attribution->findPrimaryForLead($leadB)->organization_id);

        app(TenantContext::class)->set($orgA);

        $visible = MarketingAttribution::query()->pluck('lead_id')->all();

        $this->assertContains($leadA->id, $visible);
        $this->assertNotContains($leadB->id, $visible);
    }

    // ── Regression ──────────────────────────────────────────────────────

    public function test_tracking_runtime_still_works_without_attribution(): void
    {
        $response = $this->post(route('marketing.track'), [
            'event' => 'page_view',
            'url' => 'https://example.test/pricing',
            'referrer' => 'https://google.com/',
        ]);

        $response->assertNoContent();
        $this->assertDatabaseCount('marketing_visitors', 1);
        $this->assertDatabaseCount('marketing_touches', 1);
        $this->assertDatabaseCount('marketing_attributions', 0);

        $touch = MarketingTouch::query()->sole();
        $this->assertSame('organic_search', $touch->channel);
    }

    public function test_crm_lead_flow_without_visitor_unchanged(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.store'), [
                'name' => 'Regression Lead',
                'source' => 'manual_entry',
                'priority' => 'medium',
                'status' => 'new',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'organization_id' => $organization->id,
            'name' => 'Regression Lead',
        ]);
        $this->assertDatabaseCount('marketing_attributions', 0);
    }
}
