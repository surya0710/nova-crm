<?php

namespace App\Http\Controllers;

use App\Models\CrmEmailWebhookEndpoint;
use App\Services\CrmEmailWebhookEndpointService;
use App\Services\CrmEmailWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class CrmEmailWebhookController extends Controller
{
    public function __construct(
        protected CrmEmailWebhookEndpointService $endpoints,
        protected CrmEmailWebhookService $webhooks,
    ) {}

    public function handle(Request $request, string $provider, string $token): Response
    {
        $endpoint = $this->endpoints->findByToken($provider, $token);

        if (! $endpoint instanceof CrmEmailWebhookEndpoint) {
            return response('Not found', 404);
        }

        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            $headers[$name] = implode(',', $values);
        }

        try {
            $result = $this->webhooks->ingest($endpoint, $request->getContent(), $headers);
        } catch (Throwable) {
            return response('Webhook processing unavailable.', 503);
        }

        $status = (int) ($result['http_status'] ?? 200);

        if (! ($result['ok'] ?? false)) {
            return response($result['message'] ?? 'Rejected', $status);
        }

        return response('OK', $status)->header('Content-Type', 'text/plain');
    }
}
