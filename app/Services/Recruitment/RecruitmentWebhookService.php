<?php

namespace App\Services\Recruitment;

use App\Models\Organization;
use App\Models\RecruitmentWebhookDelivery;
use App\Models\RecruitmentWebhookEndpoint;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

class RecruitmentWebhookService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    /**
     * @return array<string, string>
     */
    public function availableEvents(): array
    {
        return config('recruitment.webhooks.events', []);
    }

    public function createEndpoint(Organization $organization, array $data, User $actor): RecruitmentWebhookEndpoint
    {
        $events = $data['events'] ?? [];
        $this->assertValidEvents($events);

        $endpoint = RecruitmentWebhookEndpoint::query()->create([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'url' => $data['url'],
            'secret' => $data['secret'] ?? null,
            'events' => array_values($events),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $this->auditLogger->log($endpoint, 'recruitment_webhook_endpoint_created', [
            'url' => $endpoint->url,
            'events' => $endpoint->events,
        ], $actor);

        return $endpoint;
    }

    public function updateEndpoint(RecruitmentWebhookEndpoint $endpoint, array $data, User $actor): RecruitmentWebhookEndpoint
    {
        if (isset($data['events'])) {
            $this->assertValidEvents($data['events']);
        }

        $endpoint->update([
            'name' => $data['name'] ?? $endpoint->name,
            'url' => $data['url'] ?? $endpoint->url,
            'secret' => array_key_exists('secret', $data) ? $data['secret'] : $endpoint->secret,
            'events' => isset($data['events']) ? array_values($data['events']) : $endpoint->events,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $endpoint->is_active,
            'updated_by' => $actor->id,
        ]);

        $this->auditLogger->log($endpoint, 'recruitment_webhook_endpoint_updated', [
            'events' => $endpoint->events,
            'is_active' => $endpoint->is_active,
        ], $actor);

        return $endpoint->fresh();
    }

    /**
     * Queue outbound deliveries for a domain event key (e.g. application_submitted).
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatchEvent(Organization $organization, string $eventKey, array $payload): int
    {
        if (! array_key_exists($eventKey, $this->availableEvents())) {
            return 0;
        }

        $endpoints = RecruitmentWebhookEndpoint::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->get()
            ->filter(fn (RecruitmentWebhookEndpoint $endpoint) => in_array($eventKey, $endpoint->events ?? [], true));

        $count = 0;
        foreach ($endpoints as $endpoint) {
            $delivery = RecruitmentWebhookDelivery::query()->create([
                'organization_id' => $organization->id,
                'recruitment_webhook_endpoint_id' => $endpoint->id,
                'event_key' => $eventKey,
                'status' => 'pending',
                'attempt_count' => 0,
                'payload' => [
                    'event' => $eventKey,
                    'organization_id' => $organization->id,
                    'occurred_at' => now()->toIso8601String(),
                    'data' => $payload,
                ],
            ]);

            $this->deliver($delivery);
            $count++;
        }

        return $count;
    }

    public function deliver(RecruitmentWebhookDelivery $delivery): RecruitmentWebhookDelivery
    {
        $endpoint = $delivery->endpoint;
        if (! $endpoint || ! $endpoint->is_active) {
            $delivery->update([
                'status' => 'failed',
                'last_error' => 'Endpoint inactive or missing.',
            ]);

            return $delivery->fresh();
        }

        $attempt = (int) $delivery->attempt_count + 1;
        $timeout = (int) config('recruitment.webhooks.timeout_seconds', 10);

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'X-NovaCRM-Event' => $delivery->event_key,
                'X-NovaCRM-Delivery' => (string) $delivery->id,
            ];

            if ($endpoint->secret) {
                $body = json_encode($delivery->payload);
                $headers['X-NovaCRM-Signature'] = hash_hmac('sha256', (string) $body, (string) $endpoint->secret);
            }

            $response = Http::timeout($timeout)
                ->withHeaders($headers)
                ->post($endpoint->url, $delivery->payload);

            $ok = $response->successful();

            $delivery->update([
                'attempt_count' => $attempt,
                'http_status' => $response->status(),
                'response_body' => mb_substr($response->body(), 0, 2000),
                'status' => $ok ? 'delivered' : 'failed',
                'last_error' => $ok ? null : 'HTTP '.$response->status(),
                'delivered_at' => $ok ? now() : null,
                'next_retry_at' => $ok ? null : $this->nextRetryAt($attempt),
            ]);

            $this->auditLogger->log($delivery, 'recruitment_webhook_delivery', [
                'event_key' => $delivery->event_key,
                'status' => $delivery->status,
                'http_status' => $delivery->http_status,
                'attempt' => $attempt,
            ]);

            if (! $ok) {
                $this->maybeNotifyRepeatedFailure($delivery);
            }
        } catch (Throwable $e) {
            $delivery->update([
                'attempt_count' => $attempt,
                'status' => 'failed',
                'last_error' => $e->getMessage(),
                'next_retry_at' => $this->nextRetryAt($attempt),
            ]);

            $this->auditLogger->log($delivery, 'recruitment_webhook_delivery', [
                'event_key' => $delivery->event_key,
                'status' => 'failed',
                'attempt' => $attempt,
                'error' => $e->getMessage(),
            ]);

            $this->maybeNotifyRepeatedFailure($delivery);
        }

        return $delivery->fresh();
    }

    public function processRetries(?Organization $organization = null): int
    {
        $max = (int) config('recruitment.webhooks.max_attempts', 5);
        $query = RecruitmentWebhookDelivery::query()
            ->where('status', 'failed')
            ->where('attempt_count', '<', $max)
            ->where(function ($q) {
                $q->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now());
            })
            ->with('endpoint');

        if ($organization) {
            $query->where('organization_id', $organization->id);
        }

        $processed = 0;
        foreach ($query->limit(100)->get() as $delivery) {
            $this->deliver($delivery);
            $processed++;
        }

        return $processed;
    }

    /**
     * Map workflow trigger to webhook event key and dispatch safely.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatchFromWorkflowTrigger(Organization $organization, string $workflowTrigger, array $payload): void
    {
        try {
            $map = array_flip($this->availableEvents());
            $eventKey = $map[$workflowTrigger] ?? null;
            if (! $eventKey) {
                return;
            }
            $this->dispatchEvent($organization, $eventKey, $payload);
        } catch (Throwable) {
            // Webhook failures must never interrupt recruitment workflows.
        }
    }

    /**
     * @param  list<string>  $events
     */
    protected function assertValidEvents(array $events): void
    {
        $allowed = array_keys($this->availableEvents());
        foreach ($events as $event) {
            if (! in_array($event, $allowed, true)) {
                throw ValidationException::withMessages([
                    'events' => "Invalid webhook event [{$event}].",
                ]);
            }
        }
    }

    protected function nextRetryAt(int $attempt): \Carbon\CarbonInterface
    {
        $backoff = config('recruitment.webhooks.retry_backoff_seconds', [60, 300, 900, 3600, 7200]);
        $seconds = $backoff[min(max($attempt - 1, 0), count($backoff) - 1)] ?? 3600;

        return now()->addSeconds((int) $seconds);
    }

    protected function maybeNotifyRepeatedFailure(RecruitmentWebhookDelivery $delivery): void
    {
        $max = (int) config('recruitment.webhooks.max_attempts', 5);
        if ((int) $delivery->attempt_count < min(3, $max)) {
            return;
        }

        $endpoint = $delivery->endpoint;
        $userId = $endpoint?->created_by;
        if (! $userId) {
            return;
        }

        try {
            $this->notificationService->send(
                $delivery->organization_id,
                $userId,
                'Webhook delivery repeatedly failed',
                "Event {$delivery->event_key} failed {$delivery->attempt_count} times.",
                '/hrms/recruitment/integrations/webhooks',
            );
        } catch (Throwable) {
        }
    }
}
