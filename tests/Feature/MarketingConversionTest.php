<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\MarketingAttribution;
use App\Models\MarketingConversion;
use App\Models\MarketingSession;
use App\Models\MarketingTouch;
use App\Models\MarketingVisitor;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use App\Services\LeadConversionService;
use App\Services\LeadService;
use App\Services\MarketingAttributionService;
use App\Services\MarketingConversionService;
use App\Services\MarketingTrackingService;
use App\Services\OpportunityService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class MarketingConversionTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingConversionService $conversions;

    protected MarketingAttributionService $attribution;

    protected MarketingTrackingService $tracking;

    protected LeadService $leads;

    protected function setUp(): void
    {
        parent::setUp();

        $this->conversions = app(MarketingConversionService::class);
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
     * @return array{visitor: MarketingVisitor, session: MarketingSession, lead: Lead, attribution: MarketingAttribution}
     */
    protected function createAttributedLead(User $user, Organization $organization): array
    {
        $visitor = $this->tracking->createVisitor(['ip' => '203.0.113.50']);
        $session = $this->tracking->createSession($visitor, [
            'landing_page' => 'https://example.test/lp',
        ]);
        $this->tracking->recordPageView($session, [
            'url' => 'https://example.test/lp?utm_source=google&utm_medium=cpc',
            'referrer' => 'https://google.com/',
        ]);

        $lead = $this->leads->create([
            'organization_id' => $organization->id,
            'name' => 'Conversion Lead',
            'email' => 'conv-'.uniqid('', true).'@example.test',
            'phone' => '+1555'.random_int(100000, 999999),
            'source' => 'website',
            'status' => 'new',
            'priority' => 'medium',
            'budget' => 25000,
            'visitor_uuid' => $visitor->visitor_uuid,
            'session_uuid' => $session->session_uuid,
        ], $user);

        $attribution = $this->attribution->findPrimaryForLead($lead);

        return compact('visitor', 'session', 'lead', 'attribution');
    }

    // ── Lead events ─────────────────────────────────────────────────────

    public function test_lead_created_conversion_is_recorded_when_attributed(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        ['lead' => $lead, 'attribution' => $attribution] = $this->createAttributedLead($user, $organization);

        $this->assertNotNull($attribution);

        $event = MarketingConversion::query()
            ->where('event_name', MarketingConversion::LEAD_CREATED)
            ->where('lead_id', $lead->id)
            ->sole();

        $this->assertSame($organization->id, $event->organization_id);
        $this->assertSame($attribution->id, $event->marketing_attribution_id);
        $this->assertSame('website', $event->metadata['lead_source']);
        $this->assertArrayNotHasKey('utm_source', $event->metadata ?? []);
        $this->assertArrayNotHasKey('gclid', $event->metadata ?? []);
        $this->assertArrayNotHasKey('channel', $event->metadata ?? []);
    }

    public function test_lead_created_duplicate_prevention(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        ['lead' => $lead] = $this->createAttributedLead($user, $organization);

        $first = $this->conversions->recordLeadCreated($lead);
        $second = $this->conversions->recordLeadCreated($lead);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, MarketingConversion::query()
            ->where('event_name', MarketingConversion::LEAD_CREATED)
            ->where('lead_id', $lead->id)
            ->count());
    }

    public function test_no_attribution_means_no_conversion(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $lead = $this->leads->create([
            'organization_id' => $organization->id,
            'name' => 'Untracked Lead',
            'source' => 'manual_entry',
            'status' => 'new',
            'priority' => 'medium',
        ], $user);

        $this->assertNull($this->attribution->findPrimaryForLead($lead));
        $this->assertDatabaseCount('marketing_conversions', 0);
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    // ── Lead conversion events ──────────────────────────────────────────

    public function test_lead_conversion_records_converted_customer_and_opportunity_events(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->actingAs($user);

        ['lead' => $lead] = $this->createAttributedLead($user, $organization);
        $lead->update(['status' => 'qualified']);

        $result = app(LeadConversionService::class)->convert($lead, [
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'create_opportunity' => true,
        ], $user);

        $events = $this->conversions->historyForLead($lead->fresh())->pluck('event_name')->all();

        $this->assertContains(MarketingConversion::LEAD_CREATED, $events);
        $this->assertContains(MarketingConversion::LEAD_CONVERTED, $events);
        $this->assertContains(MarketingConversion::CUSTOMER_CREATED, $events);
        $this->assertContains(MarketingConversion::OPPORTUNITY_CREATED, $events);

        $converted = MarketingConversion::query()
            ->where('event_name', MarketingConversion::LEAD_CONVERTED)
            ->where('lead_id', $lead->id)
            ->sole();

        $this->assertSame($result['customer']->id, $converted->customer_id);
        $this->assertSame($result['opportunity']->id, $converted->opportunity_id);
    }

    public function test_conversion_without_attribution_does_not_record_events(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->actingAs($user);

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'qualified',
            'name' => 'No Attr Convert',
            'email' => 'noattr-convert@example.test',
            'created_by' => $user->id,
        ]);

        app(LeadConversionService::class)->convert($lead, [
            'name' => 'No Attr Convert',
            'email' => 'noattr-convert@example.test',
            'create_opportunity' => true,
        ], $user);

        $this->assertDatabaseCount('marketing_conversions', 0);
        $this->assertDatabaseHas('customers', ['lead_id' => $lead->id]);
        $this->assertDatabaseHas('opportunities', ['lead_id' => $lead->id]);
    }

    // ── Opportunity won ─────────────────────────────────────────────────

    public function test_opportunity_won_records_conversion_with_value(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->actingAs($user);

        ['lead' => $lead] = $this->createAttributedLead($user, $organization);
        $lead->update(['status' => 'qualified', 'budget' => 42000]);

        $result = app(LeadConversionService::class)->convert($lead, [
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'create_opportunity' => true,
        ], $user);

        $opportunity = $result['opportunity'];

        app(OpportunityService::class)->updateStage($opportunity, [
            'stage' => 'closed_won',
            'won_at' => '2026-07-16',
        ]);

        $won = MarketingConversion::query()
            ->where('event_name', MarketingConversion::OPPORTUNITY_WON)
            ->where('opportunity_id', $opportunity->id)
            ->sole();

        $this->assertEquals('42000.00', $won->event_value);
        $this->assertSame($opportunity->fresh()->currency, $won->currency);
        $this->assertSame('closed_won', $won->metadata['stage']);
        $this->assertSame(1, MarketingConversion::query()
            ->where('event_name', MarketingConversion::OPPORTUNITY_WON)
            ->where('opportunity_id', $opportunity->id)
            ->count());
    }

    public function test_opportunity_won_without_attribution_does_not_record_event(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'stage' => 'negotiation',
            'amount' => 1000,
            'created_by' => $user->id,
        ]);

        app(OpportunityService::class)->updateStage($opportunity, [
            'stage' => 'closed_won',
            'won_at' => '2026-07-16',
        ]);

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'stage' => 'closed_won',
        ]);
        $this->assertDatabaseCount('marketing_conversions', 0);
    }

    public function test_opportunity_won_duplicate_prevention(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->actingAs($user);

        ['lead' => $lead] = $this->createAttributedLead($user, $organization);
        $lead->update(['status' => 'qualified']);

        $result = app(LeadConversionService::class)->convert($lead, [
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'create_opportunity' => true,
        ], $user);

        $opportunity = $result['opportunity']->fresh(['lead', 'customer']);
        $opportunity->update(['stage' => 'closed_won', 'won_at' => '2026-07-16']);

        $first = $this->conversions->recordOpportunityWon($opportunity);
        $second = $this->conversions->recordOpportunityWon($opportunity);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second->id);
    }

    // ── Immutability ────────────────────────────────────────────────────

    public function test_conversion_events_cannot_be_updated(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        ['lead' => $lead] = $this->createAttributedLead($user, $organization);

        $event = MarketingConversion::query()
            ->where('lead_id', $lead->id)
            ->where('event_name', MarketingConversion::LEAD_CREATED)
            ->sole();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $event->update(['event_value' => 99]);
    }

    public function test_conversion_events_cannot_be_deleted(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        ['lead' => $lead] = $this->createAttributedLead($user, $organization);

        $event = MarketingConversion::query()
            ->where('lead_id', $lead->id)
            ->where('event_name', MarketingConversion::LEAD_CREATED)
            ->sole();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $event->delete();
    }

    // ── Multi-tenancy ───────────────────────────────────────────────────

    public function test_conversions_are_organization_scoped(): void
    {
        [$userA, $orgA] = $this->setupUserWithOrg();
        [$userB, $orgB] = $this->setupUserWithOrg();

        ['lead' => $leadA] = $this->createAttributedLead($userA, $orgA);
        ['lead' => $leadB] = $this->createAttributedLead($userB, $orgB);

        app(TenantContext::class)->set($orgA);

        $visibleLeadIds = MarketingConversion::query()->pluck('lead_id')->all();

        $this->assertContains($leadA->id, $visibleLeadIds);
        $this->assertNotContains($leadB->id, $visibleLeadIds);
    }

    // ── Regression ──────────────────────────────────────────────────────

    public function test_tracking_and_attribution_remain_functional(): void
    {
        $response = $this->post(route('marketing.track'), [
            'event' => 'page_view',
            'url' => 'https://example.test/pricing?utm_source=google&utm_medium=cpc',
            'referrer' => 'https://google.com/',
        ]);

        $response->assertNoContent();
        $this->assertDatabaseCount('marketing_touches', 1);
        $this->assertSame('paid_search', MarketingTouch::query()->sole()->channel);
        $this->assertDatabaseCount('marketing_conversions', 0);
    }

    public function test_pipeline_won_http_path_records_conversion_when_attributed(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->actingAs($user);

        ['lead' => $lead] = $this->createAttributedLead($user, $organization);
        $lead->update(['status' => 'qualified', 'budget' => 15000]);

        $result = app(LeadConversionService::class)->convert($lead, [
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'create_opportunity' => true,
        ], $user);

        $opportunity = $result['opportunity'];

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('pipeline.stage.update', $opportunity), [
                'stage' => 'closed_won',
                'won_at' => '2026-07-16',
            ]);

        $response->assertRedirect(route('pipeline.show', $opportunity));
        $response->assertSessionHas('status', 'opportunity-won');

        $this->assertDatabaseHas('marketing_conversions', [
            'event_name' => MarketingConversion::OPPORTUNITY_WON,
            'opportunity_id' => $opportunity->id,
            'organization_id' => $organization->id,
        ]);
    }
}
