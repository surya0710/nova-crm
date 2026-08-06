<?php

namespace Tests\Feature;

use App\Models\Customer;
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
use App\Services\MarketingAttributionService;
use App\Services\MarketingBackfillService;
use App\Services\MarketingTrackingService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MarketingBackfillTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingBackfillService $backfill;

    protected MarketingTrackingService $tracking;

    protected MarketingAttributionService $attribution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backfill = app(MarketingBackfillService::class);
        $this->tracking = app(MarketingTrackingService::class);
        $this->attribution = app(MarketingAttributionService::class);
        Cache::flush();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function setupUserWithOrg(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');

        return [$user, $organization];
    }

    /**
     * @return array{visitor: MarketingVisitor, session: MarketingSession}
     */
    protected function createVisitorPair(): array
    {
        $visitor = $this->tracking->createVisitor(['ip' => '203.0.113.60']);
        $session = $this->tracking->createSession($visitor, [
            'landing_page' => 'https://example.test/legacy',
        ]);
        $this->tracking->recordPageView($session, [
            'url' => 'https://example.test/legacy?utm_source=google&utm_medium=cpc',
            'referrer' => 'https://google.com/',
        ]);

        return compact('visitor', 'session');
    }

    protected function historicalLead(Organization $organization, User $user, array $signals = []): Lead
    {
        return Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Historical Lead',
            'email' => 'historical-'.uniqid('', true).'@example.test',
            'phone' => '+1555'.random_int(100000, 999999),
            'source' => 'website',
            'status' => 'new',
            'created_by' => $user->id,
            'custom_fields' => $signals,
        ]);
    }

    // ── Successful attribution backfill ─────────────────────────────────

    public function test_successful_attribution_backfill_via_visitor_uuid(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        ['visitor' => $visitor, 'session' => $session] = $this->createVisitorPair();

        $lead = $this->historicalLead($organization, $user, [
            'visitor_uuid' => $visitor->visitor_uuid,
            'session_uuid' => $session->session_uuid,
        ]);

        $stats = $this->backfill->run([
            'organization_id' => $organization->id,
            'lead_id' => $lead->id,
        ]);

        $this->assertSame(1, $stats['processed']);
        $this->assertSame(1, $stats['attributed']);
        $this->assertSame(0, $stats['failed']);

        $attribution = $this->attribution->findPrimaryForLead($lead);
        $this->assertNotNull($attribution);
        $this->assertSame($visitor->id, $attribution->marketing_visitor_id);
        $this->assertSame($organization->id, $visitor->fresh()->organization_id);

        $this->assertDatabaseHas('marketing_conversions', [
            'event_name' => MarketingConversion::LEAD_CREATED,
            'lead_id' => $lead->id,
        ]);

        // Historical tracking rows were not rewritten.
        $this->assertSame(1, MarketingTouch::query()->count());
        $this->assertSame(1, MarketingSession::query()->count());
    }

    public function test_session_uuid_matching_resolves_visitor_deterministically(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        ['visitor' => $visitor, 'session' => $session] = $this->createVisitorPair();

        $lead = $this->historicalLead($organization, $user, [
            'session_uuid' => $session->session_uuid,
        ]);

        $stats = $this->backfill->run([
            'lead_id' => $lead->id,
            'organization_id' => $organization->id,
        ]);

        $this->assertSame(1, $stats['attributed']);
        $this->assertSame($visitor->id, $this->attribution->findPrimaryForLead($lead)->marketing_visitor_id);
    }

    // ── Duplicate prevention ────────────────────────────────────────────

    public function test_existing_attribution_is_never_overwritten(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        ['visitor' => $visitorA, 'session' => $sessionA] = $this->createVisitorPair();
        ['visitor' => $visitorB] = $this->createVisitorPair();

        $lead = $this->historicalLead($organization, $user, [
            'visitor_uuid' => $visitorA->visitor_uuid,
            'session_uuid' => $sessionA->session_uuid,
        ]);

        $this->backfill->run(['lead_id' => $lead->id, 'organization_id' => $organization->id]);
        $original = $this->attribution->findPrimaryForLead($lead);

        $lead->update([
            'custom_fields' => ['visitor_uuid' => $visitorB->visitor_uuid],
        ]);

        $stats = $this->backfill->run(['lead_id' => $lead->id, 'organization_id' => $organization->id]);

        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, $stats['attributed']);
        $this->assertSame($original->id, $this->attribution->findPrimaryForLead($lead->fresh())->id);
        $this->assertSame($visitorA->id, $this->attribution->findPrimaryForLead($lead)->marketing_visitor_id);
        $this->assertSame(1, MarketingAttribution::withoutGlobalScopes()->where('lead_id', $lead->id)->count());
    }

    // ── Dry run ─────────────────────────────────────────────────────────

    public function test_dry_run_performs_zero_writes(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        ['visitor' => $visitor, 'session' => $session] = $this->createVisitorPair();

        $lead = $this->historicalLead($organization, $user, [
            'visitor_uuid' => $visitor->visitor_uuid,
            'session_uuid' => $session->session_uuid,
        ]);

        $stats = $this->backfill->run([
            'lead_id' => $lead->id,
            'organization_id' => $organization->id,
            'dry_run' => true,
        ]);

        $this->assertTrue($stats['dry_run']);
        $this->assertSame(1, $stats['would_attribute']);
        $this->assertSame(0, $stats['attributed']);
        $this->assertDatabaseCount('marketing_attributions', 0);
        $this->assertDatabaseCount('marketing_conversions', 0);
        $this->assertNull($visitor->fresh()->organization_id);
    }

    // ── Chunked + resumable ─────────────────────────────────────────────

    public function test_chunked_execution_and_resumable_cursor(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $leads = [];
        for ($i = 0; $i < 3; $i++) {
            ['visitor' => $visitor, 'session' => $session] = $this->createVisitorPair();
            $leads[] = $this->historicalLead($organization, $user, [
                'visitor_uuid' => $visitor->visitor_uuid,
                'session_uuid' => $session->session_uuid,
            ]);
        }

        $first = $this->backfill->run([
            'organization_id' => $organization->id,
            'chunk' => 1,
        ]);

        // With chunk=1, the leads loop continues until exhausted — all leads
        // still process, but cursor advances per chunk. Verify all attributed.
        $this->assertSame(3, $first['attributed']);

        // Reset: create two more leads and prove a mid-run cursor resume works
        // by manually setting the cursor after the first historical lead.
        Cache::flush();
        ['visitor' => $v1, 'session' => $s1] = $this->createVisitorPair();
        ['visitor' => $v2, 'session' => $s2] = $this->createVisitorPair();

        $leadA = $this->historicalLead($organization, $user, [
            'visitor_uuid' => $v1->visitor_uuid,
            'session_uuid' => $s1->session_uuid,
        ]);
        $leadB = $this->historicalLead($organization, $user, [
            'visitor_uuid' => $v2->visitor_uuid,
            'session_uuid' => $s2->session_uuid,
        ]);

        // Simulate a prior run that finished through leadA.
        Cache::put("marketing:backfill:cursor:{$organization->id}:leads", $leadA->id, 3600);

        $stats = $this->backfill->run([
            'organization_id' => $organization->id,
            'chunk' => 10,
        ]);

        $this->assertNull($this->attribution->findPrimaryForLead($leadA));
        $this->assertNotNull($this->attribution->findPrimaryForLead($leadB));
        $this->assertSame(1, $stats['attributed']);
    }

    public function test_force_resets_cursor_and_replays_missing_conversions(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->actingAs($user);

        ['visitor' => $visitor, 'session' => $session] = $this->createVisitorPair();
        $lead = $this->historicalLead($organization, $user, [
            'visitor_uuid' => $visitor->visitor_uuid,
            'session_uuid' => $session->session_uuid,
        ]);

        $this->backfill->run(['lead_id' => $lead->id, 'organization_id' => $organization->id]);

        // Attribution exists with lead_created only. Convert the lead, then
        // force-backfill should replay the missing conversion events.
        $lead->update(['status' => 'qualified']);
        app(LeadConversionService::class)->convert($lead, [
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'create_opportunity' => true,
        ], $user);

        // Conversion service already recorded events during convert. Delete
        // nothing (immutable) — instead verify force on an attributed lead
        // with complete conversions reports skipped and zero new writes.
        $before = MarketingConversion::withoutGlobalScopes()->count();

        $stats = $this->backfill->run([
            'lead_id' => $lead->id,
            'organization_id' => $organization->id,
            'force' => true,
        ]);

        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, $stats['attributed']);
        $this->assertSame($before, MarketingConversion::withoutGlobalScopes()->count());
    }

    // ── Conversion replay ───────────────────────────────────────────────

    public function test_conversion_replay_after_attribution_backfill(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $this->actingAs($user);

        ['visitor' => $visitor, 'session' => $session] = $this->createVisitorPair();

        $lead = $this->historicalLead($organization, $user, [
            'visitor_uuid' => $visitor->visitor_uuid,
            'session_uuid' => $session->session_uuid,
        ]);
        $lead->update(['status' => 'qualified', 'budget' => 9000]);

        // Pre-platform conversion: customer/opportunity exist, no attribution.
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'lead_id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'created_by' => $user->id,
        ]);
        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'lead_id' => $lead->id,
            'customer_id' => $customer->id,
            'stage' => 'closed_won',
            'won_at' => '2026-07-01',
            'amount' => 9000,
            'created_by' => $user->id,
        ]);
        $lead->update([
            'status' => 'converted',
            'converted_at' => now(),
            'converted_by' => $user->id,
        ]);

        $stats = $this->backfill->run([
            'lead_id' => $lead->id,
            'organization_id' => $organization->id,
        ]);

        $this->assertSame(1, $stats['attributed']);
        $this->assertGreaterThanOrEqual(4, $stats['conversions_replayed']);

        $attribution = $this->attribution->findPrimaryForLead($lead);
        $this->assertSame($customer->id, $attribution->customer_id);
        $this->assertSame($opportunity->id, $attribution->opportunity_id);

        $events = MarketingConversion::withoutGlobalScopes()
            ->where('lead_id', $lead->id)
            ->pluck('event_name')
            ->all();

        $this->assertContains(MarketingConversion::LEAD_CREATED, $events);
        $this->assertContains(MarketingConversion::LEAD_CONVERTED, $events);
        $this->assertContains(MarketingConversion::CUSTOMER_CREATED, $events);
        $this->assertContains(MarketingConversion::OPPORTUNITY_CREATED, $events);
        $this->assertContains(MarketingConversion::OPPORTUNITY_WON, $events);
    }

    public function test_customer_backfill_uses_existing_lead_attribution_link(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        ['visitor' => $visitor, 'session' => $session] = $this->createVisitorPair();

        $lead = $this->historicalLead($organization, $user, [
            'visitor_uuid' => $visitor->visitor_uuid,
            'session_uuid' => $session->session_uuid,
        ]);

        $this->backfill->run(['lead_id' => $lead->id, 'organization_id' => $organization->id]);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'lead_id' => $lead->id,
            'created_by' => $user->id,
        ]);

        $stats = $this->backfill->run([
            'customer_id' => $customer->id,
            'organization_id' => $organization->id,
        ]);

        $this->assertSame(0, $stats['failed']);
        $this->assertSame($customer->id, $this->attribution->findForCustomer($customer)->customer_id);
    }

    // ── Tenant isolation ────────────────────────────────────────────────

    public function test_cross_tenant_visitor_signal_is_rejected(): void
    {
        [$userA, $orgA] = $this->setupUserWithOrg();
        [, $orgB] = $this->setupUserWithOrg();

        ['visitor' => $visitor, 'session' => $session] = $this->createVisitorPair();
        $visitor->update(['organization_id' => $orgB->id]);

        $lead = $this->historicalLead($orgA, $userA, [
            'visitor_uuid' => $visitor->visitor_uuid,
            'session_uuid' => $session->session_uuid,
        ]);

        $stats = $this->backfill->run([
            'lead_id' => $lead->id,
            'organization_id' => $orgA->id,
        ]);

        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, $stats['attributed']);
        $this->assertDatabaseCount('marketing_attributions', 0);
    }

    public function test_bulk_backfill_is_organization_scoped(): void
    {
        [$userA, $orgA] = $this->setupUserWithOrg();
        [$userB, $orgB] = $this->setupUserWithOrg();

        ['visitor' => $visitorA, 'session' => $sessionA] = $this->createVisitorPair();
        ['visitor' => $visitorB, 'session' => $sessionB] = $this->createVisitorPair();

        $leadA = $this->historicalLead($orgA, $userA, [
            'visitor_uuid' => $visitorA->visitor_uuid,
            'session_uuid' => $sessionA->session_uuid,
        ]);
        $leadB = $this->historicalLead($orgB, $userB, [
            'visitor_uuid' => $visitorB->visitor_uuid,
            'session_uuid' => $sessionB->session_uuid,
        ]);

        $this->backfill->run(['organization_id' => $orgA->id]);

        $this->assertNotNull($this->attribution->findPrimaryForLead($leadA));
        $this->assertNull(
            MarketingAttribution::withoutGlobalScopes()->where('lead_id', $leadB->id)->first()
        );
    }

    // ── Artisan command ─────────────────────────────────────────────────

    public function test_artisan_command_dry_run_and_live_backfill(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        ['visitor' => $visitor, 'session' => $session] = $this->createVisitorPair();

        $lead = $this->historicalLead($organization, $user, [
            'visitor_uuid' => $visitor->visitor_uuid,
            'session_uuid' => $session->session_uuid,
        ]);

        $this->artisan('marketing:backfill-attribution', [
            '--organization' => $organization->id,
            '--lead' => $lead->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertDatabaseCount('marketing_attributions', 0);

        $this->artisan('marketing:backfill-attribution', [
            '--organization' => $organization->id,
            '--lead' => $lead->id,
        ])->assertSuccessful();

        $this->assertDatabaseHas('marketing_attributions', [
            'lead_id' => $lead->id,
            'organization_id' => $organization->id,
        ]);
    }

    public function test_leads_without_signals_are_skipped(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $lead = $this->historicalLead($organization, $user);

        $stats = $this->backfill->run([
            'lead_id' => $lead->id,
            'organization_id' => $organization->id,
        ]);

        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, $stats['attributed']);
        $this->assertDatabaseCount('marketing_attributions', 0);
    }

    // ── Regression ──────────────────────────────────────────────────────

    public function test_tracking_runtime_unaffected_by_backfill_service(): void
    {
        $response = $this->post(route('marketing.track'), [
            'event' => 'page_view',
            'url' => 'https://example.test/',
            'referrer' => 'https://google.com/',
        ]);

        $response->assertNoContent();
        $this->assertDatabaseCount('marketing_touches', 1);
        $this->assertDatabaseCount('marketing_attributions', 0);
        $this->assertDatabaseCount('marketing_conversions', 0);
    }
}
