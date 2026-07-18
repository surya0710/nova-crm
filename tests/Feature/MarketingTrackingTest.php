<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\MarketingSession;
use App\Models\MarketingTouch;
use App\Models\MarketingVisitor;
use App\Models\Organization;
use App\Models\User;
use App\Services\MarketingTrackingService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketingTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingTrackingService $tracking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracking = app(MarketingTrackingService::class);
    }

    // ── Visitor ─────────────────────────────────────────────────────────

    public function test_service_creates_visitor_with_uuid_and_seen_context(): void
    {
        $visitor = $this->tracking->createVisitor([
            'ip' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 (Test)',
        ]);

        $this->assertDatabaseHas('marketing_visitors', [
            'id' => $visitor->id,
            'organization_id' => null,
            'first_ip' => '203.0.113.10',
            'last_ip' => '203.0.113.10',
            'first_user_agent' => 'Mozilla/5.0 (Test)',
            'last_user_agent' => 'Mozilla/5.0 (Test)',
        ]);

        $this->assertTrue(Str::isUuid($visitor->visitor_uuid));
        $this->assertNotNull($visitor->first_seen_at);
        $this->assertTrue($visitor->first_seen_at->equalTo($visitor->last_seen_at));
    }

    public function test_visitor_lookup_by_uuid(): void
    {
        $visitor = MarketingVisitor::factory()->create();
        MarketingVisitor::factory()->create();

        $found = $this->tracking->findVisitor($visitor->visitor_uuid);

        $this->assertNotNull($found);
        $this->assertSame($visitor->id, $found->id);

        $this->assertNull($this->tracking->findVisitor((string) Str::uuid()));
    }

    public function test_update_last_seen_preserves_first_touch_context(): void
    {
        $visitor = MarketingVisitor::factory()->create([
            'first_seen_at' => now()->subDays(5),
            'last_seen_at' => now()->subDays(5),
            'first_ip' => '203.0.113.1',
            'last_ip' => '203.0.113.1',
            'first_user_agent' => 'Original UA',
            'last_user_agent' => 'Original UA',
        ]);

        $updated = $this->tracking->updateLastSeen($visitor, [
            'ip' => '198.51.100.7',
            'user_agent' => 'Newer UA',
        ]);

        $this->assertSame('203.0.113.1', $updated->first_ip);
        $this->assertSame('Original UA', $updated->first_user_agent);
        $this->assertSame('198.51.100.7', $updated->last_ip);
        $this->assertSame('Newer UA', $updated->last_user_agent);
        $this->assertTrue($updated->last_seen_at->greaterThan($updated->first_seen_at));
    }

    // ── Session ─────────────────────────────────────────────────────────

    public function test_service_creates_session_for_visitor(): void
    {
        $visitor = MarketingVisitor::factory()->create();

        $session = $this->tracking->createSession($visitor, [
            'landing_page' => 'https://example.test/pricing',
            'referrer' => 'https://google.com/',
            'device_type' => 'mobile',
        ]);

        $this->assertDatabaseHas('marketing_sessions', [
            'id' => $session->id,
            'visitor_id' => $visitor->id,
            'landing_page' => 'https://example.test/pricing',
            'referrer' => 'https://google.com/',
            'device_type' => 'mobile',
        ]);

        $this->assertTrue(Str::isUuid($session->session_uuid));
        $this->assertNull($session->ended_at);
    }

    public function test_close_session_sets_ended_at_and_is_idempotent(): void
    {
        $session = MarketingSession::factory()->create();

        $closed = $this->tracking->closeSession($session);
        $this->assertNotNull($closed->ended_at);

        $firstEndedAt = $closed->ended_at;
        $this->travel(10)->minutes();

        $closedAgain = $this->tracking->closeSession($closed);
        $this->assertTrue($firstEndedAt->equalTo($closedAgain->ended_at));
    }

    public function test_active_session_lookup_returns_only_open_session(): void
    {
        $visitor = MarketingVisitor::factory()->create();

        MarketingSession::factory()->create([
            'visitor_id' => $visitor->id,
            'started_at' => now()->subHours(3),
            'ended_at' => now()->subHours(2),
        ]);

        $open = MarketingSession::factory()->create([
            'visitor_id' => $visitor->id,
            'started_at' => now()->subMinutes(10),
            'ended_at' => null,
        ]);

        $active = $this->tracking->activeSessionFor($visitor);

        $this->assertNotNull($active);
        $this->assertSame($open->id, $active->id);
    }

    public function test_creating_new_session_closes_previous_open_session(): void
    {
        $visitor = MarketingVisitor::factory()->create();

        $first = $this->tracking->createSession($visitor, [], now()->subHour());
        $second = $this->tracking->createSession($visitor);

        $this->assertNotNull($first->fresh()->ended_at);
        $this->assertNull($second->ended_at);
        $this->assertSame($second->id, $this->tracking->activeSessionFor($visitor)->id);
    }

    public function test_session_lookup_by_uuid(): void
    {
        $session = MarketingSession::factory()->create();

        $found = $this->tracking->findSession($session->session_uuid);

        $this->assertNotNull($found);
        $this->assertSame($session->id, $found->id);
    }

    // ── Touch ───────────────────────────────────────────────────────────

    public function test_service_creates_touch_for_session(): void
    {
        $session = MarketingSession::factory()->create();

        $touch = $this->tracking->createTouch($session, [
            'channel' => 'paid_search',
            'source' => 'google',
            'medium' => 'cpc',
            'campaign' => 'summer-sale',
            'content' => 'ad-variant-b',
            'term' => 'crm software',
            'landing_page' => 'https://example.test/lp',
            'referrer' => 'https://google.com/',
        ]);

        $this->assertDatabaseHas('marketing_touches', [
            'id' => $touch->id,
            'session_id' => $session->id,
            'channel' => 'paid_search',
            'source' => 'google',
            'medium' => 'cpc',
            'campaign' => 'summer-sale',
            'content' => 'ad-variant-b',
            'term' => 'crm software',
        ]);

        $this->assertNotNull($touch->occurred_at);
    }

    public function test_relationship_integrity_across_visitor_session_touch(): void
    {
        $visitor = MarketingVisitor::factory()->create();
        $session = MarketingSession::factory()->create(['visitor_id' => $visitor->id]);
        $touch = MarketingTouch::factory()->create(['session_id' => $session->id]);

        $this->assertSame($visitor->id, $session->visitor->id);
        $this->assertSame($session->id, $touch->session->id);
        $this->assertTrue($visitor->sessions->contains($session));
        $this->assertTrue($session->touchpoints->contains($touch));
        $this->assertSame($visitor->id, $touch->session->visitor->id);
    }

    // ── Database ────────────────────────────────────────────────────────

    public function test_deleting_visitor_cascades_to_sessions_and_touches(): void
    {
        $visitor = MarketingVisitor::factory()->create();
        $session = MarketingSession::factory()->create(['visitor_id' => $visitor->id]);
        $touch = MarketingTouch::factory()->create(['session_id' => $session->id]);

        $visitor->delete();

        $this->assertDatabaseMissing('marketing_sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('marketing_touches', ['id' => $touch->id]);
    }

    public function test_deleting_organization_cascades_to_owned_visitors_only(): void
    {
        $organization = Organization::factory()->create();
        $owned = MarketingVisitor::factory()->create(['organization_id' => $organization->id]);
        $unowned = MarketingVisitor::factory()->create();

        $organization->delete();

        $this->assertDatabaseMissing('marketing_visitors', ['id' => $owned->id]);
        $this->assertDatabaseHas('marketing_visitors', ['id' => $unowned->id]);
    }

    public function test_visitor_uuid_is_unique(): void
    {
        $visitor = MarketingVisitor::factory()->create();

        $this->expectException(QueryException::class);

        MarketingVisitor::factory()->create(['visitor_uuid' => $visitor->visitor_uuid]);
    }

    public function test_expected_indexes_exist(): void
    {
        $visitorIndexes = collect(Schema::getIndexes('marketing_visitors'))
            ->pluck('columns')->map(fn ($columns) => implode(',', $columns));
        $sessionIndexes = collect(Schema::getIndexes('marketing_sessions'))
            ->pluck('columns')->map(fn ($columns) => implode(',', $columns));
        $touchIndexes = collect(Schema::getIndexes('marketing_touches'))
            ->pluck('columns')->map(fn ($columns) => implode(',', $columns));

        $this->assertTrue($visitorIndexes->contains('visitor_uuid'), 'visitor_uuid index missing');
        $this->assertTrue($visitorIndexes->contains('organization_id'), 'organization_id index missing');

        $this->assertTrue($sessionIndexes->contains('session_uuid'), 'session_uuid index missing');
        $this->assertTrue($sessionIndexes->contains('visitor_id,ended_at'), 'active session index missing');
        $this->assertTrue($sessionIndexes->contains('started_at'), 'started_at index missing');

        $this->assertTrue($touchIndexes->contains('session_id,occurred_at'), 'touch retrieval index missing');
    }

    // ── Regression ──────────────────────────────────────────────────────

    public function test_crm_lead_flow_is_unaffected_by_tracking_infrastructure(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.store'), [
                'name' => 'Tracking Regression Lead',
                'source' => 'website',
                'priority' => 'medium',
                'status' => 'new',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'organization_id' => $organization->id,
            'name' => 'Tracking Regression Lead',
        ]);

        $this->assertDatabaseCount('marketing_visitors', 0);
        $this->assertDatabaseCount('marketing_sessions', 0);
        $this->assertDatabaseCount('marketing_touches', 0);

        $this->assertSame(1, Lead::withoutGlobalScopes()->count());
    }
}
