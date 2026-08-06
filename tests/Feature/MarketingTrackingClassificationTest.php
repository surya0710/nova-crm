<?php

namespace Tests\Feature;

use App\Models\MarketingSession;
use App\Models\MarketingTouch;
use App\Services\MarketingChannelClassificationService;
use App\Services\MarketingTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingTrackingClassificationTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingChannelClassificationService $classifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classifier = app(MarketingChannelClassificationService::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function classify(?string $url, ?string $referrer = null): array
    {
        return $this->classifier->classify($url, $referrer);
    }

    // ── UTM capture ─────────────────────────────────────────────────────

    public function test_all_utm_parameters_are_captured_and_normalized(): void
    {
        $result = $this->classify(
            'https://example.test/lp?utm_source=%20Google%20&utm_medium=CPC&utm_campaign=Summer%20Sale&utm_term=crm%20software&utm_content=Variant-B',
        );

        $this->assertSame('google', $result['source']);
        $this->assertSame('cpc', $result['medium']);
        $this->assertSame('Summer Sale', $result['campaign']);
        $this->assertSame('crm software', $result['term']);
        $this->assertSame('Variant-B', $result['content']);
    }

    public function test_empty_utm_values_become_null_not_empty_strings(): void
    {
        $result = $this->classify('https://example.test/?utm_source=&utm_campaign=%20%20');

        $this->assertNull($result['source']);
        $this->assertNull($result['campaign']);
        $this->assertSame('direct', $result['channel']);
    }

    public function test_campaign_term_and_content_preserve_casing(): void
    {
        $result = $this->classify('https://example.test/?utm_source=Newsletter&utm_medium=Email&utm_campaign=Q3-Launch');

        $this->assertSame('newsletter', $result['source']);
        $this->assertSame('email', $result['medium']);
        $this->assertSame('Q3-Launch', $result['campaign']);
    }

    // ── Click identifiers ───────────────────────────────────────────────

    public function test_gclid_is_captured_and_classifies_paid_search(): void
    {
        $result = $this->classify('https://example.test/?gclid=Cj0KCQjw_test_123');

        $this->assertSame('Cj0KCQjw_test_123', $result['gclid']);
        $this->assertSame('paid_search', $result['channel']);
        $this->assertSame('google', $result['source']);
        $this->assertSame('cpc', $result['medium']);
    }

    public function test_msclkid_is_captured_and_classifies_paid_search(): void
    {
        $result = $this->classify('https://example.test/?msclkid=abc123def456');

        $this->assertSame('abc123def456', $result['msclkid']);
        $this->assertSame('paid_search', $result['channel']);
        $this->assertSame('bing', $result['source']);
    }

    public function test_fbclid_is_captured_and_classifies_paid_social(): void
    {
        $result = $this->classify('https://example.test/?fbclid=IwAR_test_456');

        $this->assertSame('IwAR_test_456', $result['fbclid']);
        $this->assertSame('paid_social', $result['channel']);
        $this->assertSame('facebook', $result['source']);
    }

    public function test_click_id_takes_precedence_over_referrer_but_utm_values_win_for_source(): void
    {
        $result = $this->classify(
            'https://example.test/?gclid=xyz&utm_source=google&utm_medium=cpc&utm_campaign=brand',
            'https://partner.test/blog',
        );

        $this->assertSame('paid_search', $result['channel']);
        $this->assertSame('google', $result['source']);
        $this->assertSame('cpc', $result['medium']);
        $this->assertSame('brand', $result['campaign']);
        $this->assertSame('partner.test', $result['referrer_host']);
    }

    // ── Referrer detection ──────────────────────────────────────────────

    public function test_referrer_host_is_normalized(): void
    {
        $result = $this->classify('https://example.test/', 'https://WWW.Google.co.uk/search?q=crm');

        $this->assertSame('google.co.uk', $result['referrer_host']);
        $this->assertSame('organic_search', $result['channel']);
        $this->assertSame('google', $result['source']);
        $this->assertSame('organic', $result['medium']);
    }

    public function test_search_engine_referrers_classify_organic_search(): void
    {
        foreach ([
            'https://www.bing.com/search?q=x' => 'bing',
            'https://duckduckgo.com/?q=x' => 'duckduckgo',
            'https://www.baidu.com/s?wd=x' => 'baidu',
            'https://search.yahoo.com/search?p=x' => 'yahoo',
        ] as $referrer => $expectedSource) {
            $result = $this->classify('https://example.test/', $referrer);

            $this->assertSame('organic_search', $result['channel'], $referrer);
            $this->assertSame($expectedSource, $result['source'], $referrer);
        }
    }

    public function test_social_referrers_classify_organic_social(): void
    {
        foreach ([
            'https://www.facebook.com/groups/x' => 'facebook',
            'https://l.instagram.com/redirect' => 'instagram',
            'https://www.linkedin.com/feed/' => 'linkedin',
            'https://t.co/abc' => 'x',
            'https://www.threads.net/@user' => 'threads',
            'https://www.reddit.com/r/crm/' => 'reddit',
            'https://youtu.be/xyz' => 'youtube',
        ] as $referrer => $expectedSource) {
            $result = $this->classify('https://example.test/', $referrer);

            $this->assertSame('organic_social', $result['channel'], $referrer);
            $this->assertSame($expectedSource, $result['source'], $referrer);
            $this->assertSame('social', $result['medium'], $referrer);
        }
    }

    public function test_unknown_domain_referrer_classifies_referral(): void
    {
        $result = $this->classify('https://example.test/', 'https://blog.partner-site.test/article');

        $this->assertSame('referral', $result['channel']);
        $this->assertSame('blog.partner-site.test', $result['source']);
        $this->assertSame('referral', $result['medium']);
    }

    public function test_self_referral_is_ignored_and_classifies_direct(): void
    {
        $result = $this->classify('https://example.test/pricing', 'https://www.example.test/features');

        $this->assertSame('direct', $result['channel']);
        $this->assertNull($result['referrer_host']);
    }

    public function test_missing_referrer_is_handled_gracefully(): void
    {
        $result = $this->classify('https://example.test/', null);

        $this->assertSame('direct', $result['channel']);
        $this->assertNull($result['referrer_host']);
    }

    // ── Channel classification via UTM ──────────────────────────────────

    public function test_paid_search_via_utm(): void
    {
        $result = $this->classify('https://example.test/?utm_source=google&utm_medium=cpc');

        $this->assertSame('paid_search', $result['channel']);
    }

    public function test_paid_social_via_utm_source(): void
    {
        $result = $this->classify('https://example.test/?utm_source=facebook&utm_medium=cpc');

        $this->assertSame('paid_social', $result['channel']);
    }

    public function test_paid_social_via_utm_medium(): void
    {
        $result = $this->classify('https://example.test/?utm_source=quora&utm_medium=paid_social');

        $this->assertSame('paid_social', $result['channel']);
    }

    public function test_organic_social_via_utm(): void
    {
        $result = $this->classify('https://example.test/?utm_source=linkedin&utm_medium=post');

        $this->assertSame('organic_social', $result['channel']);
    }

    public function test_organic_search_via_utm_organic_medium(): void
    {
        $result = $this->classify('https://example.test/?utm_source=google&utm_medium=organic');

        $this->assertSame('organic_search', $result['channel']);
    }

    public function test_email_via_utm_medium(): void
    {
        foreach (['email', 'newsletter'] as $medium) {
            $result = $this->classify("https://example.test/?utm_source=mailer&utm_medium={$medium}");

            $this->assertSame('email', $result['channel'], $medium);
        }
    }

    public function test_display_via_utm_medium(): void
    {
        $result = $this->classify('https://example.test/?utm_source=adnetwork&utm_medium=display');

        $this->assertSame('display', $result['channel']);
    }

    public function test_referral_via_utm_medium(): void
    {
        $result = $this->classify('https://example.test/?utm_source=partner&utm_medium=referral');

        $this->assertSame('referral', $result['channel']);
        $this->assertSame('partner', $result['source']);
    }

    public function test_unrecognized_utm_combination_classifies_other(): void
    {
        $result = $this->classify('https://example.test/?utm_source=podcast&utm_medium=audio');

        $this->assertSame('other', $result['channel']);
        $this->assertSame('podcast', $result['source']);
        $this->assertSame('audio', $result['medium']);
    }

    public function test_no_signals_classify_direct(): void
    {
        $result = $this->classify('https://example.test/');

        $this->assertSame('direct', $result['channel']);
        $this->assertNull($result['source']);
        $this->assertNull($result['medium']);
    }

    // ── Persistence through the tracking pipeline ───────────────────────

    public function test_page_view_persists_classification_on_touch(): void
    {
        $session = MarketingSession::factory()->create();

        $touch = app(MarketingTrackingService::class)->recordPageView($session, [
            'url' => 'https://example.test/lp?utm_source=google&utm_medium=cpc&utm_campaign=summer&gclid=abc123',
            'referrer' => 'https://www.google.com/',
        ]);

        $this->assertDatabaseHas('marketing_touches', [
            'id' => $touch->id,
            'session_id' => $session->id,
            'channel' => 'paid_search',
            'source' => 'google',
            'medium' => 'cpc',
            'campaign' => 'summer',
            'gclid' => 'abc123',
            'referrer_host' => 'google.com',
        ]);
    }

    public function test_landing_page_is_stored_without_tracking_parameters(): void
    {
        $session = MarketingSession::factory()->create();

        $touch = app(MarketingTrackingService::class)->recordPageView($session, [
            'url' => 'https://example.test/lp?page=2&utm_source=google&utm_medium=cpc&gclid=abc',
        ]);

        $this->assertSame('https://example.test/lp?page=2', $touch->landing_page);
        $this->assertSame('abc', $touch->gclid);
    }

    public function test_tracking_endpoint_classifies_end_to_end(): void
    {
        $response = $this->post(route('marketing.track'), [
            'event' => 'page_view',
            'url' => 'https://example.test/lp?fbclid=IwAR999&utm_campaign=retargeting-q3',
            'referrer' => 'https://m.facebook.com/',
        ]);

        $response->assertNoContent();

        $touch = MarketingTouch::query()->sole();

        $this->assertSame('paid_social', $touch->channel);
        $this->assertSame('facebook', $touch->source);
        $this->assertSame('IwAR999', $touch->fbclid);
        $this->assertSame('retargeting-q3', $touch->campaign);
        $this->assertSame('m.facebook.com', $touch->referrer_host);
    }

    public function test_classifier_performs_no_database_writes(): void
    {
        $this->classify(
            'https://example.test/?utm_source=google&utm_medium=cpc&gclid=abc',
            'https://google.com/',
        );

        $this->assertDatabaseCount('marketing_visitors', 0);
        $this->assertDatabaseCount('marketing_sessions', 0);
        $this->assertDatabaseCount('marketing_touches', 0);
    }
}
