<?php

namespace App\Http\Middleware;

use App\Services\MarketingTrackingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Establishes anonymous visitor identity and session continuity.
 *
 * All persistence is delegated to MarketingTrackingService; this middleware
 * only reads cookies, exposes the resolved records to the request lifecycle,
 * and re-issues cookies on the response.
 */
class MarketingTrackingMiddleware
{
    public function __construct(protected MarketingTrackingService $tracking) {}

    public function handle(Request $request, Closure $next): Response
    {
        $context = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        $visitor = $this->tracking->resolveVisitor(
            $request->cookie($this->visitorCookieName()),
            $context,
        );

        $session = $this->tracking->resolveSession(
            $visitor,
            $request->cookie($this->sessionCookieName()),
            [
                'landing_page' => $request->input('landing_page') ?? $request->input('url'),
                'referrer' => $request->input('referrer') ?? $request->headers->get('referer'),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
            ],
        );

        $request->attributes->set('marketing_visitor', $visitor);
        $request->attributes->set('marketing_session', $session);

        $response = $next($request);

        $response->headers->setCookie($this->makeCookie(
            $this->visitorCookieName(),
            $visitor->visitor_uuid,
            (int) config('marketing.tracking.visitor_lifetime_minutes'),
        ));

        // Re-issued on every response so the expiry slides with activity.
        $response->headers->setCookie($this->makeCookie(
            $this->sessionCookieName(),
            $session->session_uuid,
            $this->tracking->sessionTimeoutMinutes(),
        ));

        return $response;
    }

    protected function makeCookie(string $name, string $value, int $minutes): Cookie
    {
        return cookie(
            name: $name,
            value: $value,
            minutes: $minutes,
            secure: config('session.secure'),
            httpOnly: true,
            sameSite: 'lax',
        );
    }

    protected function visitorCookieName(): string
    {
        return config('marketing.tracking.visitor_cookie');
    }

    protected function sessionCookieName(): string
    {
        return config('marketing.tracking.session_cookie');
    }
}
