<?php

namespace App\Http\Controllers;

use App\Services\MarketingProviderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;
use Throwable;

/**
 * Meta (and future provider) webhook HTTP entrypoint (P7C.6).
 *
 * Verifies subscription challenges and records signed deliveries.
 * Does not process lead events or create CRM entities.
 */
class MetaWebhookController extends Controller
{
    public function __construct(
        protected MarketingProviderService $providers,
    ) {}

    public function handle(Request $request, string $provider): Response
    {
        if ($request->isMethod('get')) {
            return $this->verify($request, $provider);
        }

        return $this->receive($request, $provider);
    }

    protected function verify(Request $request, string $provider): Response
    {
        try {
            $result = $this->providers->verifyWebhook($provider, [
                'hub_mode' => $request->query('hub_mode', $request->query('hub.mode')),
                'hub_verify_token' => $request->query('hub_verify_token', $request->query('hub.verify_token')),
                'hub_challenge' => $request->query('hub_challenge', $request->query('hub.challenge')),
            ]);
        } catch (InvalidArgumentException $e) {
            return response($e->getMessage(), 404);
        }

        if (! ($result['ok'] ?? false)) {
            return response($result['message'] ?? 'Forbidden', 403);
        }

        return response((string) $result['challenge'], 200)
            ->header('Content-Type', 'text/plain');
    }

    protected function receive(Request $request, string $provider): Response
    {
        $rawBody = $request->getContent();

        $headers = [];
        foreach (['X-Hub-Signature-256', 'x-hub-signature-256'] as $header) {
            if ($request->headers->has($header)) {
                $headers[$header] = (string) $request->headers->get($header);
            }
        }

        try {
            $result = $this->providers->ingestWebhook($provider, $rawBody, $headers);
        } catch (InvalidArgumentException $e) {
            return response($e->getMessage(), 404);
        } catch (Throwable) {
            return response('Webhook processing unavailable.', 503);
        }

        $status = (int) ($result['http_status'] ?? (($result['ok'] ?? false) ? 200 : 401));

        // Meta expects a quick 200 on accepted deliveries. Errors use 4xx/5xx.
        if ($result['ok'] ?? false) {
            return response('EVENT_RECEIVED', $status)->header('Content-Type', 'text/plain');
        }

        return response($result['message'] ?? 'Rejected', $status);
    }
}
