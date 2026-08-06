<?php

namespace App\Services;

use App\Models\MarketingSession;
use App\Models\MarketingTouch;
use App\Models\MarketingVisitor;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Single write authority for tracking infrastructure (P7B.1).
 *
 * All visitor, session, and touch writes must go through this service.
 * No attribution, provider, or lead logic lives here.
 */
class MarketingTrackingService
{
    public function __construct(
        protected MarketingChannelClassificationService $classifier,
    ) {}

    /**
     * Find the visitor for a cookie UUID, or create a fresh identity.
     * Last-seen is only touched when older than the configured granularity,
     * so rapid page views do not cause redundant writes.
     *
     * @param  array{ip?: string|null, user_agent?: string|null}  $context
     */
    public function resolveVisitor(?string $visitorUuid, array $context = []): MarketingVisitor
    {
        $visitor = $visitorUuid ? $this->findVisitor($visitorUuid) : null;

        if (! $visitor) {
            return $this->createVisitor($context);
        }

        if ($this->isStale($visitor->last_seen_at)) {
            $visitor = $this->updateLastSeen($visitor, $context);
        }

        return $visitor;
    }

    /**
     * Resolve the session for a request: continue the session referenced by
     * the cookie when it is still active, otherwise roll over to a new one.
     * A session is expired once it is closed or inactive longer than the
     * configured timeout.
     *
     * @param  array<string, mixed>  $attributes  used only when a new session starts
     */
    public function resolveSession(MarketingVisitor $visitor, ?string $sessionUuid, array $attributes = []): MarketingSession
    {
        $session = null;

        if ($sessionUuid) {
            $session = MarketingSession::query()
                ->where('session_uuid', $sessionUuid)
                ->where('visitor_id', $visitor->id)
                ->first();
        }

        $session ??= $this->activeSessionFor($visitor);

        if ($session && ! $this->isSessionExpired($session)) {
            return $this->recordActivity($session);
        }

        if ($session && $session->ended_at === null) {
            // Expired by inactivity: close at the last known activity.
            $this->closeSession($session, $session->last_activity_at ?? $session->started_at);
        }

        return $this->createSession($visitor, $attributes);
    }

    public function isSessionExpired(MarketingSession $session): bool
    {
        if ($session->ended_at !== null) {
            return true;
        }

        $lastActivity = $session->last_activity_at ?? $session->started_at;

        return $lastActivity->lte(now()->subMinutes($this->sessionTimeoutMinutes()));
    }

    public function recordActivity(MarketingSession $session, ?CarbonInterface $at = null): MarketingSession
    {
        $at ??= now();
        $lastActivity = $session->last_activity_at ?? $session->started_at;

        if (! $this->isStale($lastActivity, $at)) {
            return $session;
        }

        $session->update(['last_activity_at' => $at]);

        return $session->fresh();
    }

    /**
     * Record a page_view tracking event as a classified touch. Classification
     * is delegated to MarketingChannelClassificationService; persistence
     * stays here.
     *
     * @param  array{landing_page?: string|null, url?: string|null, referrer?: string|null}  $payload
     */
    public function recordPageView(MarketingSession $session, array $payload, ?CarbonInterface $occurredAt = null): MarketingTouch
    {
        $url = $payload['url'] ?? null;
        $referrer = $payload['referrer'] ?? null;

        $classification = $this->classifier->classify($url, $referrer);

        $touch = $this->createTouch($session, [
            ...$classification,
            'landing_page' => $this->classifier->stripTrackingParameters($payload['landing_page'] ?? $url),
            'referrer' => $referrer,
        ], $occurredAt);

        $this->recordActivity($session, $touch->occurred_at);

        return $touch;
    }

    public function findVisitor(string $visitorUuid): ?MarketingVisitor
    {
        return MarketingVisitor::query()
            ->where('visitor_uuid', $visitorUuid)
            ->first();
    }

    /**
     * @param  array{ip?: string|null, user_agent?: string|null}  $context
     */
    public function createVisitor(array $context = [], ?CarbonInterface $seenAt = null): MarketingVisitor
    {
        $seenAt ??= now();

        return MarketingVisitor::query()->create([
            'visitor_uuid' => (string) Str::uuid(),
            'first_seen_at' => $seenAt,
            'last_seen_at' => $seenAt,
            'first_ip' => $context['ip'] ?? null,
            'last_ip' => $context['ip'] ?? null,
            'first_user_agent' => $this->truncateUserAgent($context['user_agent'] ?? null),
            'last_user_agent' => $this->truncateUserAgent($context['user_agent'] ?? null),
        ]);
    }

    /**
     * @param  array{ip?: string|null, user_agent?: string|null}  $context
     */
    public function updateLastSeen(MarketingVisitor $visitor, array $context = [], ?CarbonInterface $seenAt = null): MarketingVisitor
    {
        $attributes = ['last_seen_at' => $seenAt ?? now()];

        if (array_key_exists('ip', $context) && $context['ip'] !== null) {
            $attributes['last_ip'] = $context['ip'];
        }

        if (array_key_exists('user_agent', $context) && $context['user_agent'] !== null) {
            $attributes['last_user_agent'] = $this->truncateUserAgent($context['user_agent']);
        }

        $visitor->update($attributes);

        return $visitor->fresh();
    }

    /**
     * Start a new session for a visitor. Any session still open for the
     * visitor is closed first so at most one session is active per visitor.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createSession(MarketingVisitor $visitor, array $attributes = [], ?CarbonInterface $startedAt = null): MarketingSession
    {
        $startedAt ??= now();

        return DB::transaction(function () use ($visitor, $attributes, $startedAt) {
            $openSession = $this->activeSessionFor($visitor);

            if ($openSession) {
                $this->closeSession($openSession, $startedAt);
            }

            return MarketingSession::query()->create([
                'visitor_id' => $visitor->id,
                'session_uuid' => (string) Str::uuid(),
                'started_at' => $startedAt,
                'last_activity_at' => $startedAt,
                'landing_page' => $this->truncateUrl($attributes['landing_page'] ?? null),
                'referrer' => $this->truncateUrl($attributes['referrer'] ?? null),
                'user_agent' => $this->truncateUserAgent($attributes['user_agent'] ?? null),
                'ip_address' => $attributes['ip_address'] ?? null,
                'device_type' => $attributes['device_type'] ?? null,
                'browser' => $attributes['browser'] ?? null,
                'operating_system' => $attributes['operating_system'] ?? null,
            ]);
        });
    }

    public function findSession(string $sessionUuid): ?MarketingSession
    {
        return MarketingSession::query()
            ->where('session_uuid', $sessionUuid)
            ->first();
    }

    public function activeSessionFor(MarketingVisitor $visitor): ?MarketingSession
    {
        return MarketingSession::query()
            ->where('visitor_id', $visitor->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();
    }

    public function closeSession(MarketingSession $session, ?CarbonInterface $endedAt = null): MarketingSession
    {
        if ($session->ended_at !== null) {
            return $session;
        }

        $session->update(['ended_at' => $endedAt ?? now()]);

        return $session->fresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createTouch(MarketingSession $session, array $attributes = [], ?CarbonInterface $occurredAt = null): MarketingTouch
    {
        return MarketingTouch::query()->create([
            'session_id' => $session->id,
            'occurred_at' => $occurredAt ?? now(),
            'channel' => $attributes['channel'] ?? null,
            'source' => $attributes['source'] ?? null,
            'medium' => $attributes['medium'] ?? null,
            'campaign' => $attributes['campaign'] ?? null,
            'content' => $attributes['content'] ?? null,
            'term' => $attributes['term'] ?? null,
            'gclid' => $attributes['gclid'] ?? null,
            'fbclid' => $attributes['fbclid'] ?? null,
            'msclkid' => $attributes['msclkid'] ?? null,
            'landing_page' => $this->truncateUrl($attributes['landing_page'] ?? null),
            'referrer' => $this->truncateUrl($attributes['referrer'] ?? null),
            'referrer_host' => $attributes['referrer_host'] ?? null,
        ]);
    }

    public function sessionTimeoutMinutes(): int
    {
        return max(1, (int) config('marketing.tracking.session_timeout_minutes'));
    }

    protected function isStale(?CarbonInterface $timestamp, ?CarbonInterface $reference = null): bool
    {
        if ($timestamp === null) {
            return true;
        }

        $granularity = max(0, (int) config('marketing.tracking.activity_granularity_seconds'));

        return $timestamp->lte(($reference ?? now())->subSeconds($granularity));
    }

    protected function truncateUrl(?string $url): ?string
    {
        return $url === null ? null : Str::limit(trim($url), 2048, '');
    }

    protected function truncateUserAgent(?string $userAgent): ?string
    {
        return $userAgent === null ? null : Str::limit(trim($userAgent), 1024, '');
    }
}
