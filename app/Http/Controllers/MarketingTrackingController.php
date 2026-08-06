<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackPageViewRequest;
use App\Models\MarketingSession;
use App\Services\MarketingTrackingService;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class MarketingTrackingController extends Controller
{
    public function __construct(protected MarketingTrackingService $tracking) {}

    public function store(TrackPageViewRequest $request): Response
    {
        /** @var MarketingSession $session */
        $session = $request->attributes->get('marketing_session');

        $occurredAt = $request->validated('occurred_at')
            ? Carbon::parse($request->validated('occurred_at'))
            : null;

        // Client clocks are untrusted; never record a touch in the future.
        if ($occurredAt !== null && $occurredAt->isAfter(now())) {
            $occurredAt = now();
        }

        $this->tracking->recordPageView($session, [
            'landing_page' => $request->validated('landing_page'),
            'url' => $request->validated('url'),
            'referrer' => $request->validated('referrer'),
        ], $occurredAt);

        return response()->noContent();
    }
}
