<?php

namespace App\Services;

use App\Models\CrmEmailMessage;
use App\Models\CrmEmailWebhookEndpoint;
use App\Models\CrmEmailWebhookEvent;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CrmEmailWebhookService
{
    public function __construct(
        protected CrmEmailDeliveryService $delivery,
        protected CrmEmailConversationService $conversations,
    ) {}

    /**
     * @param  array<string, string>  $headers
     * @return array{ok: bool, http_status: int, message?: string, processed?: int, duplicate?: int}
     */
    public function ingest(CrmEmailWebhookEndpoint $endpoint, string $rawBody, array $headers): array
    {
        if (! $this->verify($endpoint, $rawBody, $headers)) {
            Log::warning('crm.email.webhook.rejected', [
                'organization_id' => $endpoint->organization_id,
                'provider' => $endpoint->provider,
                'reason' => 'signature',
            ]);

            return ['ok' => false, 'http_status' => Response::HTTP_UNAUTHORIZED, 'message' => 'Invalid signature'];
        }

        $events = $this->parseEvents($endpoint->provider, $rawBody);

        $processed = 0;
        $duplicate = 0;

        foreach ($events as $event) {
            $result = $this->applyEvent($endpoint, $event);
            if ($result === 'duplicate') {
                $duplicate++;
            } elseif ($result === 'processed') {
                $processed++;
            }
        }

        return [
            'ok' => true,
            'http_status' => Response::HTTP_OK,
            'processed' => $processed,
            'duplicate' => $duplicate,
        ];
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function verify(CrmEmailWebhookEndpoint $endpoint, string $rawBody, array $headers): bool
    {
        $secret = $endpoint->decryptedSigningSecret();

        if ($endpoint->provider === 'mailgun') {
            return $this->verifyMailgun($secret, $rawBody, $headers);
        }

        if ($endpoint->provider === 'sendgrid') {
            return $this->verifySendGrid($secret, $rawBody, $headers);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    protected function applyEvent(CrmEmailWebhookEndpoint $endpoint, array $event): string
    {
        $eventId = (string) ($event['id'] ?? '');
        $type = (string) ($event['type'] ?? '');

        if ($eventId === '' || $type === '') {
            return 'ignored';
        }

        $existing = CrmEmailWebhookEvent::withoutGlobalScopes()
            ->where('organization_id', $endpoint->organization_id)
            ->where('provider', $endpoint->provider)
            ->where('provider_event_id', $eventId)
            ->first();

        if ($existing) {
            return 'duplicate';
        }

        $message = $this->resolveMessage($endpoint, $event);

        $record = CrmEmailWebhookEvent::withoutGlobalScopes()->create([
            'organization_id' => $endpoint->organization_id,
            'endpoint_id' => $endpoint->id,
            'crm_email_message_id' => $message?->id,
            'provider' => $endpoint->provider,
            'provider_event_id' => $eventId,
            'event' => $type,
            'payload' => $event['payload'] ?? $event,
            'processed_at' => now(),
        ]);

        if (! $message) {
            Log::info('crm.email.webhook.unmatched', [
                'organization_id' => $endpoint->organization_id,
                'provider' => $endpoint->provider,
                'event_id' => $eventId,
            ]);

            return 'processed';
        }

        if ((int) $message->organization_id !== (int) $endpoint->organization_id) {
            Log::warning('crm.email.webhook.org_mismatch', [
                'endpoint_org' => $endpoint->organization_id,
                'message_org' => $message->organization_id,
                'message_id' => $message->id,
            ]);

            $record->update(['crm_email_message_id' => null]);

            return 'ignored';
        }

        $metadata = [
            'last_webhook_event' => $type,
            'last_webhook_event_id' => $eventId,
            'last_webhook_at' => now()->toIso8601String(),
        ];

        match ($type) {
            'delivered' => $this->delivery->markDelivered($message, $metadata),
            'bounced' => $this->delivery->markBounced(
                $message,
                $event['bounce_type'] ?? null,
                $event['reason'] ?? null,
                $metadata,
            ),
            'failed' => $this->delivery->markFailed($message, (string) ($event['reason'] ?? 'Provider reported failure'), $metadata),
            'deferred' => $this->delivery->recordDeferred($message, $metadata),
            default => null,
        };

        if ($message->conversation) {
            $this->conversations->refresh($message->conversation);
        }

        return 'processed';
    }

    /**
     * @param  array<string, mixed>  $event
     */
    protected function resolveMessage(CrmEmailWebhookEndpoint $endpoint, array $event): ?CrmEmailMessage
    {
        $orgId = (int) $endpoint->organization_id;

        $query = CrmEmailMessage::withoutGlobalScopes()->where('organization_id', $orgId);

        foreach (['konnect_email_id', 'email_id'] as $key) {
            $id = (int) ($event[$key] ?? 0);
            if ($id > 0) {
                $found = (clone $query)->whereKey($id)->first();
                if ($found) {
                    return $found;
                }
            }
        }

        foreach ([
            'provider_message_id' => ['provider_message_id', 'sg_message_id'],
            'rfc_message_id' => ['rfc_message_id', 'smtp_id', 'message_id'],
        ] as $column => $keys) {
            foreach ($keys as $key) {
                $value = $this->normalizeId($event[$key] ?? null);
                if ($value === '') {
                    continue;
                }

                $found = (clone $query)->where(function ($inner) use ($column, $value) {
                    $inner->where($column, $value)
                        ->orWhere($column, '<'.$value.'>');
                })->first();

                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function parseEvents(string $provider, string $rawBody): array
    {
        $decoded = json_decode($rawBody, true);

        if ($provider === 'sendgrid') {
            $items = is_array($decoded) ? $decoded : [];
            if (isset($items['event'])) {
                $items = [$items];
            }

            $events = [];
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $events[] = [
                    'id' => (string) ($item['sg_event_id'] ?? $item['sg_message_id'].'-'.($item['event'] ?? '').'-'.($item['timestamp'] ?? uniqid())),
                    'type' => $this->normalizeSendGridEvent((string) ($item['event'] ?? '')),
                    'provider_message_id' => $item['sg_message_id'] ?? null,
                    'smtp_id' => $item['smtp-id'] ?? $item['smtp_id'] ?? null,
                    'konnect_email_id' => $item['konnect_email_id'] ?? ($item['unique_args']['konnect_email_id'] ?? null),
                    'bounce_type' => $item['type'] ?? $item['bounce_classification'] ?? null,
                    'reason' => $item['reason'] ?? $item['response'] ?? null,
                    'payload' => $item,
                ];
            }

            return $events;
        }

        if ($provider === 'mailgun') {
            $item = is_array($decoded) ? ($decoded['event-data'] ?? $decoded) : [];
            if (! is_array($item) || $item === []) {
                return [];
            }

            $userVariables = $item['user-variables'] ?? [];

            return [[
                'id' => (string) ($item['id'] ?? ($item['message']['headers']['message-id'] ?? uniqid('mailgun-', true))),
                'type' => $this->normalizeMailgunEvent((string) ($item['event'] ?? '')),
                'message_id' => $item['message']['headers']['message-id'] ?? null,
                'rfc_message_id' => $item['message']['headers']['message-id'] ?? null,
                'konnect_email_id' => $userVariables['konnect_email_id'] ?? null,
                'bounce_type' => $item['severity'] ?? null,
                'reason' => $item['reason'] ?? ($item['delivery-status']['description'] ?? null),
                'payload' => $item,
            ]];
        }

        return [];
    }

    protected function normalizeSendGridEvent(string $event): string
    {
        return match ($event) {
            'delivered' => 'delivered',
            'bounce', 'dropped' => 'bounced',
            'deferred' => 'deferred',
            'blocked' => 'failed',
            default => $event,
        };
    }

    protected function normalizeMailgunEvent(string $event): string
    {
        return match ($event) {
            'delivered' => 'delivered',
            'bounced', 'failed' => $event === 'bounced' ? 'bounced' : 'failed',
            'rejected' => 'failed',
            'delayed' => 'deferred',
            default => $event,
        };
    }

    /**
     * @param  array<string, string>  $headers
     */
    protected function verifyMailgun(?string $secret, string $rawBody, array $headers): bool
    {
        if (! filled($secret)) {
            return false;
        }

        $decoded = json_decode($rawBody, true);
        $signature = is_array($decoded) ? ($decoded['signature'] ?? []) : [];

        $timestamp = (string) ($signature['timestamp'] ?? $this->header($headers, 'X-Mailgun-Timestamp'));
        $token = (string) ($signature['token'] ?? $this->header($headers, 'X-Mailgun-Token'));
        $sig = (string) ($signature['signature'] ?? $this->header($headers, 'X-Mailgun-Signature'));

        if ($timestamp === '' || $token === '' || $sig === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.$token, $secret);

        return hash_equals($expected, $sig);
    }

    /**
     * SendGrid Event Webhook: prefer HMAC of the raw body with the stored signing secret.
     * URL token already scopes the request to one organization.
     *
     * @param  array<string, string>  $headers
     */
    protected function verifySendGrid(?string $secret, string $rawBody, array $headers): bool
    {
        if (! filled($secret)) {
            return false;
        }

        $provided = $this->header($headers, 'X-Twilio-Email-Event-Webhook-Signature')
            ?: $this->header($headers, 'X-SendGrid-Signature')
            ?: $this->bearerToken($headers);

        if ($provided === '') {
            return false;
        }

        $timestamp = $this->header($headers, 'X-Twilio-Email-Event-Webhook-Timestamp');
        $payload = $timestamp !== '' ? $timestamp.$rawBody : $rawBody;
        $expected = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($expected, $provided) || hash_equals($secret, $provided);
    }

    /**
     * @param  array<string, string>  $headers
     */
    protected function header(array $headers, string $name): string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp((string) $key, $name) === 0) {
                return (string) $value;
            }
        }

        return '';
    }

    /**
     * @param  array<string, string>  $headers
     */
    protected function bearerToken(array $headers): string
    {
        $authorization = $this->header($headers, 'Authorization');
        if (str_starts_with(strtolower($authorization), 'bearer ')) {
            return trim(substr($authorization, 7));
        }

        return '';
    }

    protected function normalizeId(mixed $value): string
    {
        return trim((string) $value, " \t\n\r\0\x0B<>");
    }
}
