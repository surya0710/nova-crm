<?php

namespace Tests\Feature;

use App\Models\MarketingSession;
use App\Models\MarketingTouch;
use App\Models\MarketingVisitor;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketingTrackingRuntimeTest extends TestCase
{
    use RefreshDatabase;

    protected function visitorCookie(): string
    {
        return config('marketing.tracking.visitor_cookie');
    }

    protected function sessionCookie(): string
    {
        return config('marketing.tracking.session_cookie');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function track(array $overrides = [])
    {
        return $this->post(route('marketing.track'), [
            'event' => 'page_view',
            'url' => 'https://example.test/pricing',
            'referrer' => 'https://google.com/',
            ...$overrides,
        ]);
    }

    // ── Visitor ─────────────────────────────────────────────────────────

    public function test_first_visit_creates_visitor_session_and_touch_with_cookies(): void
    {
        $response = $this->track();

        $response->assertNoContent();
        $this->assertDatabaseCount('marketing_visitors', 1);
        $this->assertDatabaseCount('marketing_sessions', 1);
        $this->assertDatabaseCount('marketing_touches', 1);

        $visitor = MarketingVisitor::query()->sole();
        $session = MarketingSession::query()->sole();

        $response->assertCookie($this->visitorCookie(), $visitor->visitor_uuid);
        $response->assertCookie($this->sessionCookie(), $session->session_uuid);
    }

    public function test_returning_visitor_is_recognized_and_not_duplicated(): void
    {
        $this->track();
        $visitor = MarketingVisitor::query()->sole();

        $this->travel(2)->minutes();

        $response = $this
            ->withCookie($this->visitorCookie(), $visitor->visitor_uuid)
            ->withCookie($this->sessionCookie(), MarketingSession::query()->sole()->session_uuid)
            ->post(route('marketing.track'), [
                'event' => 'page_view',
                'url' => 'https://example.test/features',
            ]);

        $response->assertNoContent();
        $this->assertDatabaseCount('marketing_visitors', 1);
        $this->assertTrue($visitor->fresh()->last_seen_at->greaterThan($visitor->last_seen_at));
    }

    public function test_cookie_persistence_reissues_same_visitor_uuid(): void
    {
        $this->track();
        $visitor = MarketingVisitor::query()->sole();

        $response = $this
            ->withCookie($this->visitorCookie(), $visitor->visitor_uuid)
            ->track();

        $response->assertCookie($this->visitorCookie(), $visitor->visitor_uuid);
        $this->assertDatabaseCount('marketing_visitors', 1);
    }

    public function test_unknown_visitor_cookie_creates_fresh_identity(): void
    {
        $staleUuid = (string) Str::uuid();

        $response = $this
            ->withCookie($this->visitorCookie(), $staleUuid)
            ->track();

        $response->assertNoContent();
        $this->assertDatabaseCount('marketing_visitors', 1);
        $this->assertNotSame($staleUuid, MarketingVisitor::query()->sole()->visitor_uuid);
    }

    // ── Session ─────────────────────────────────────────────────────────

    public function test_session_continues_within_timeout(): void
    {
        $this->track();
        $visitor = MarketingVisitor::query()->sole();
        $session = MarketingSession::query()->sole();

        $this->travel(5)->minutes();

        $this->withCookie($this->visitorCookie(), $visitor->visitor_uuid)
            ->withCookie($this->sessionCookie(), $session->session_uuid)
            ->track(['url' => 'https://example.test/contact']);

        $this->assertDatabaseCount('marketing_sessions', 1);
        $this->assertDatabaseCount('marketing_touches', 2);
        $this->assertNull($session->fresh()->ended_at);
    }

    public function test_session_rolls_over_after_inactivity_timeout(): void
    {
        $this->track();
        $visitor = MarketingVisitor::query()->sole();
        $first = MarketingSession::query()->sole();

        $timeout = (int) config('marketing.tracking.session_timeout_minutes');
        $this->travel($timeout + 5)->minutes();

        $response = $this
            ->withCookie($this->visitorCookie(), $visitor->visitor_uuid)
            ->withCookie($this->sessionCookie(), $first->session_uuid)
            ->track(['url' => 'https://example.test/return-visit']);

        $this->assertDatabaseCount('marketing_visitors', 1);
        $this->assertDatabaseCount('marketing_sessions', 2);

        $first = $first->fresh();
        $this->assertNotNull($first->ended_at);

        $second = MarketingSession::query()->whereNull('ended_at')->sole();
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame($visitor->id, $second->visitor_id);
        $response->assertCookie($this->sessionCookie(), $second->session_uuid);

        $this->assertSame(1, $second->touchpoints()->count());
    }

    public function test_missing_session_cookie_reuses_active_session(): void
    {
        $this->track();
        $visitor = MarketingVisitor::query()->sole();

        $this->travel(2)->minutes();

        $this->withCookie($this->visitorCookie(), $visitor->visitor_uuid)
            ->track(['url' => 'https://example.test/second']);

        $this->assertDatabaseCount('marketing_sessions', 1);
        $this->assertDatabaseCount('marketing_touches', 2);
    }

    public function test_last_activity_updates_only_when_meaningful(): void
    {
        $this->track();
        $session = MarketingSession::query()->sole();
        $initialActivity = $session->last_activity_at;

        $visitor = MarketingVisitor::query()->sole();

        // Immediate second view: below granularity, no timestamp write.
        $this->withCookie($this->visitorCookie(), $visitor->visitor_uuid)
            ->withCookie($this->sessionCookie(), $session->session_uuid)
            ->track(['url' => 'https://example.test/fast-follow']);

        $this->assertTrue($initialActivity->equalTo($session->fresh()->last_activity_at));

        // Later view: beyond granularity, timestamp advances.
        $this->travel(3)->minutes();

        $this->withCookie($this->visitorCookie(), $visitor->visitor_uuid)
            ->withCookie($this->sessionCookie(), $session->session_uuid)
            ->track(['url' => 'https://example.test/later']);

        $this->assertTrue($session->fresh()->last_activity_at->greaterThan($initialActivity));
    }

    // ── Tracking ────────────────────────────────────────────────────────

    public function test_page_view_creates_touch_with_context(): void
    {
        $this->track([
            'url' => 'https://example.test/landing',
            'landing_page' => 'https://example.test/landing',
            'referrer' => 'https://partner.test/blog',
        ]);

        $touch = MarketingTouch::query()->sole();

        $this->assertSame('https://example.test/landing', $touch->landing_page);
        $this->assertSame('https://partner.test/blog', $touch->referrer);
        $this->assertNotNull($touch->occurred_at);

        // Classified as referral since 7B.3; no UTM data on this view.
        $this->assertSame('referral', $touch->channel);
        $this->assertNull($touch->campaign);
    }

    public function test_future_client_timestamp_is_clamped_to_server_time(): void
    {
        $this->track(['occurred_at' => now()->addDays(3)->toIso8601String()]);

        $touch = MarketingTouch::query()->sole();

        $this->assertTrue($touch->occurred_at->lte(now()));
    }

    public function test_invalid_payloads_are_rejected(): void
    {
        $this->postJson(route('marketing.track'), [])->assertStatus(422);

        $this->postJson(route('marketing.track'), [
            'event' => 'not_a_supported_event',
            'url' => 'https://example.test/',
        ])->assertStatus(422)->assertJsonValidationErrors('event');

        $this->postJson(route('marketing.track'), [
            'event' => 'page_view',
            'url' => 'not-a-url',
        ])->assertStatus(422)->assertJsonValidationErrors('url');

        $this->postJson(route('marketing.track'), [
            'event' => 'page_view',
            'url' => 'https://example.test/',
            'occurred_at' => 'not-a-date',
        ])->assertStatus(422)->assertJsonValidationErrors('occurred_at');

        $this->assertDatabaseCount('marketing_touches', 0);
    }

    public function test_tracking_endpoint_is_rate_limited(): void
    {
        config(['marketing.tracking.rate_limit_per_minute' => 5]);

        for ($i = 0; $i < 5; $i++) {
            $this->track()->assertNoContent();
        }

        $this->track()->assertStatus(429);
    }

    // ── Regression ──────────────────────────────────────────────────────

    public function test_crm_pages_are_not_tracked_and_remain_functional(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index'));

        $response->assertOk();
        $response->assertCookieMissing($this->visitorCookie());

        $this->assertDatabaseCount('marketing_visitors', 0);
        $this->assertDatabaseCount('marketing_sessions', 0);
        $this->assertDatabaseCount('marketing_touches', 0);
    }
}
