<?php

namespace App\Services\Marketing\Providers;

use App\Contracts\MarketingProviderAssetDiscoveryInterface;
use App\Contracts\MarketingProviderInterface;
use App\Contracts\MarketingProviderLeadFormSyncInterface;
use App\Contracts\MarketingProviderLeadImportInterface;
use App\Contracts\MarketingProviderLeadRetrievalInterface;
use App\Contracts\MarketingProviderWebhookInterface;
use App\Models\MarketingProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Meta Business provider adapter (P7C.2–P7C.6).
 *
 * OAuth, asset discovery, lead-form metadata sync, manual lead-entry fetch,
 * webhook verification / signature validation, and offline conversion uploads.
 * Campaign sync remains unsupported.
 * Persistence is owned exclusively by MarketingProviderService.
 */
class MetaMarketingProvider implements MarketingProviderAssetDiscoveryInterface, MarketingProviderInterface, MarketingProviderLeadFormSyncInterface, MarketingProviderLeadImportInterface, MarketingProviderLeadRetrievalInterface, MarketingProviderWebhookInterface
{
    public const SLUG = 'meta';

    /** @var list<string> */
    public const STANDARD_FIELD_KEYS = [
        'full_name',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'phone',
        'company_name',
        'company',
    ];

    public function __construct(
        protected MetaGraphClient $graph,
    ) {}

    public function slug(): string
    {
        return self::SLUG;
    }

    public function displayName(): string
    {
        return (string) (config('marketing.providers.catalog.meta.name') ?? 'Meta Business');
    }

    public function capabilities(): array
    {
        return ['oauth', 'asset_discovery', 'lead_form_sync', 'lead_import', 'webhooks', 'offline_conversions'];
    }

    public function authorize(MarketingProvider $provider, array $context = []): array
    {
        $phase = $context['phase'] ?? (isset($context['code']) ? 'callback' : 'start');

        return match ($phase) {
            'start' => $this->beginAuthorization($provider, $context),
            'callback' => $this->completeAuthorization($provider, $context),
            default => throw new RuntimeException("Unsupported Meta authorize phase [{$phase}]."),
        };
    }

    public function refreshCredentials(MarketingProvider $provider): array
    {
        $accessToken = $provider->credential?->access_token;

        if (! $accessToken) {
            throw new RuntimeException('Meta provider has no access token to refresh.');
        }

        $token = $this->graph->exchangeForLongLivedToken($accessToken);

        return $this->normalizeCredentials($token, $provider->credential?->scopes);
    }

    public function revoke(MarketingProvider $provider): void
    {
        $accessToken = $provider->credential?->access_token;

        if (! $accessToken) {
            return;
        }

        try {
            $this->graph->revokePermissions($accessToken);
        } catch (Throwable) {
            // Best-effort remote revoke; local disconnect proceeds in the service.
        }
    }

    public function synchronize(MarketingProvider $provider, array $options = []): array
    {
        return [
            'ok' => false,
            'accounts' => [],
            'campaigns' => [],
            'message' => 'Not yet implemented: Meta campaign synchronization.',
        ];
    }

    public function verifyWebhook(array $query): array
    {
        $mode = $this->nullableString($query['hub_mode'] ?? $query['hub.mode'] ?? null);
        $token = $this->nullableString($query['hub_verify_token'] ?? $query['hub.verify_token'] ?? null);
        $challenge = $this->nullableString($query['hub_challenge'] ?? $query['hub.challenge'] ?? null);

        if ($mode !== 'subscribe') {
            return [
                'ok' => false,
                'challenge' => null,
                'message' => 'Meta webhook verification requires hub.mode=subscribe.',
            ];
        }

        $expected = $this->nullableString(config('marketing.providers.meta.webhook_verify_token'));

        if ($expected === null) {
            return [
                'ok' => false,
                'challenge' => null,
                'message' => 'Meta webhook verify token is not configured (META_WEBHOOK_VERIFY_TOKEN).',
            ];
        }

        if ($token === null || ! hash_equals($expected, $token)) {
            return [
                'ok' => false,
                'challenge' => null,
                'message' => 'Meta webhook verify token mismatch.',
            ];
        }

        if ($challenge === null) {
            return [
                'ok' => false,
                'challenge' => null,
                'message' => 'Meta webhook verification is missing hub.challenge.',
            ];
        }

        return [
            'ok' => true,
            'challenge' => $challenge,
            'message' => null,
        ];
    }

    public function validateAndNormalizeWebhook(string $rawBody, array $payload, array $headers = []): array
    {
        $signature = $this->extractHubSignature($headers);

        if ($signature === null) {
            return [
                'ok' => false,
                'event' => null,
                'normalized' => null,
                'delivery_id' => null,
                'signature' => null,
                'message' => 'Missing X-Hub-Signature-256 header.',
                'http_status' => 401,
            ];
        }

        $appSecret = $this->nullableString(config('marketing.providers.meta.client_secret'));

        if ($appSecret === null) {
            return [
                'ok' => false,
                'event' => null,
                'normalized' => null,
                'delivery_id' => null,
                'signature' => $signature,
                'message' => 'Meta app secret is not configured (META_APP_SECRET).',
                'http_status' => 503,
            ];
        }

        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $appSecret);

        if (! hash_equals($expected, $signature)) {
            return [
                'ok' => false,
                'event' => null,
                'normalized' => null,
                'delivery_id' => null,
                'signature' => $signature,
                'message' => 'Invalid Meta webhook signature.',
                'http_status' => 401,
            ];
        }

        if ($payload === [] || ! isset($payload['object'])) {
            return [
                'ok' => false,
                'event' => null,
                'normalized' => null,
                'delivery_id' => hash('sha256', $rawBody),
                'signature' => $signature,
                'message' => 'Malformed Meta webhook payload.',
                'http_status' => 400,
            ];
        }

        $normalized = $this->normalizeWebhookPayload($payload);

        return [
            'ok' => true,
            'event' => $normalized['event_type'],
            'normalized' => $normalized,
            'delivery_id' => hash('sha256', $rawBody),
            'signature' => $signature,
            'message' => null,
            'http_status' => 200,
        ];
    }

    public function receiveWebhook(MarketingProvider $provider, array $payload, array $headers = []): array
    {
        $rawBody = $headers['raw_body'] ?? null;

        if (! is_string($rawBody) || $rawBody === '') {
            try {
                $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                return [
                    'ok' => false,
                    'event' => null,
                    'normalized' => null,
                    'message' => 'Malformed Meta webhook payload.',
                ];
            }
        }

        $headersWithoutRaw = $headers;
        unset($headersWithoutRaw['raw_body']);

        return $this->validateAndNormalizeWebhook($rawBody, $payload, $headersWithoutRaw);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     event_type: string,
     *     object: string|null,
     *     entries: list<array<string, mixed>>,
     *     leadgen: list<array<string, mixed>>,
     *     change_count: int
     * }
     */
    protected function normalizeWebhookPayload(array $payload): array
    {
        $entries = [];
        $leadgen = [];
        $changeCount = 0;
        $eventType = 'unknown';

        $rawEntries = $payload['entry'] ?? [];
        if (! is_array($rawEntries)) {
            $rawEntries = [];
        }

        foreach ($rawEntries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $changes = [];
            $rawChanges = $entry['changes'] ?? [];
            if (! is_array($rawChanges)) {
                $rawChanges = [];
            }

            foreach ($rawChanges as $change) {
                if (! is_array($change)) {
                    continue;
                }

                $changeCount++;
                $field = $this->nullableString($change['field'] ?? null) ?? 'unknown';
                if ($eventType === 'unknown') {
                    $eventType = $field;
                }

                $value = is_array($change['value'] ?? null) ? $change['value'] : [];
                $normalizedChange = [
                    'field' => $field,
                    'value' => $value,
                ];
                $changes[] = $normalizedChange;

                if ($field === 'leadgen') {
                    $leadgen[] = [
                        'leadgen_id' => $this->nullableString($value['leadgen_id'] ?? null),
                        'page_id' => $this->nullableString($value['page_id'] ?? ($entry['id'] ?? null)),
                        'form_id' => $this->nullableString($value['form_id'] ?? null),
                        'ad_id' => $this->nullableString($value['ad_id'] ?? null),
                        'adgroup_id' => $this->nullableString($value['adgroup_id'] ?? null),
                        'campaign_id' => $this->nullableString($value['campaign_id'] ?? null),
                        'created_time' => $value['created_time'] ?? null,
                    ];
                }
            }

            $entries[] = [
                'id' => $this->nullableString($entry['id'] ?? null),
                'time' => $entry['time'] ?? null,
                'changes' => $changes,
            ];
        }

        return [
            'event_type' => $eventType,
            'object' => $this->nullableString($payload['object'] ?? null),
            'entries' => $entries,
            'leadgen' => $leadgen,
            'change_count' => $changeCount,
        ];
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    protected function extractHubSignature(array $headers): ?string
    {
        foreach (['X-Hub-Signature-256', 'x-hub-signature-256', 'X-HUB-SIGNATURE-256'] as $key) {
            $value = $headers[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return trim($value);
            }
        }

        // Normalize PHP CGI-style header keys.
        foreach ($headers as $key => $value) {
            if (! is_string($key) || ! is_string($value) || $value === '') {
                continue;
            }

            if (strtolower(str_replace('_', '-', $key)) === 'x-hub-signature-256') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * Upload normalized CRM conversion DTOs via the Meta Conversions API.
     * Does not persist Eloquent models — MarketingProviderService owns history.
     *
     * @param  list<array<string, mixed>>  $conversions
     * @return array<string, mixed>
     */
    public function uploadConversions(MarketingProvider $provider, array $conversions): array
    {
        $accessToken = $provider->credential?->access_token;
        $configuration = $provider->credential?->configuration ?? [];
        $pixelId = $this->nullableString($configuration['pixel_id'] ?? null);

        if (! $accessToken) {
            return [
                'ok' => false,
                'uploaded' => 0,
                'failed' => count($conversions),
                'skipped' => 0,
                'status' => MarketingProvider::STATUS_DISCONNECTED,
                'message' => 'Meta provider is not connected.',
                'results' => [],
            ];
        }

        if ($provider->credential?->isExpired()) {
            return [
                'ok' => false,
                'uploaded' => 0,
                'failed' => count($conversions),
                'skipped' => 0,
                'status' => MarketingProvider::STATUS_EXPIRED,
                'message' => 'Meta credentials expired.',
                'results' => [],
            ];
        }

        if ($pixelId === null) {
            return [
                'ok' => false,
                'uploaded' => 0,
                'failed' => count($conversions),
                'skipped' => 0,
                'status' => MarketingProvider::STATUS_ERROR,
                'message' => 'Meta pixel_id is not configured for this organization.',
                'results' => [],
            ];
        }

        $uploaded = 0;
        $failed = 0;
        $results = [];
        $fatalStatus = null;
        $fatalMessage = null;

        foreach ($conversions as $index => $conversion) {
            if (! is_array($conversion)) {
                $failed++;
                $results[] = [
                    'ok' => false,
                    'conversion_id' => null,
                    'message' => 'Conversion payload must be an array.',
                ];

                continue;
            }

            $conversionId = $conversion['conversion_id'] ?? $conversion['id'] ?? null;

            try {
                $event = $this->normalizeConversionEvent($conversion);
                $response = $this->graph->sendPixelEvents($pixelId, $accessToken, [$event]);
                $uploaded++;
                $results[] = [
                    'ok' => true,
                    'conversion_id' => $conversionId,
                    'external_event_id' => $event['event_id'] ?? null,
                    'provider_event_name' => $event['event_name'] ?? null,
                    'events_received' => $response['events_received'] ?? null,
                    'fbtrace_id' => $response['fbtrace_id'] ?? null,
                    'message' => null,
                ];
            } catch (Throwable $e) {
                $failed++;
                $results[] = [
                    'ok' => false,
                    'conversion_id' => $conversionId,
                    'message' => $e->getMessage(),
                ];

                if ($this->looksLikeExpiredToken($e->getMessage()) || $this->looksLikeRevokedPermissions($e->getMessage())) {
                    $fatalStatus = $this->looksLikeExpiredToken($e->getMessage())
                        ? MarketingProvider::STATUS_EXPIRED
                        : MarketingProvider::STATUS_ERROR;
                    $fatalMessage = $e->getMessage();

                    // Remaining conversions cannot succeed with a bad token.
                    for ($i = $index + 1; $i < count($conversions); $i++) {
                        $remaining = $conversions[$i];
                        $failed++;
                        $results[] = [
                            'ok' => false,
                            'conversion_id' => is_array($remaining)
                                ? ($remaining['conversion_id'] ?? $remaining['id'] ?? null)
                                : null,
                            'message' => $fatalMessage,
                        ];
                    }

                    break;
                }
            }
        }

        $ok = $failed === 0;

        return [
            'ok' => $ok,
            'uploaded' => $uploaded,
            'failed' => $failed,
            'skipped' => 0,
            'status' => $fatalStatus,
            'message' => $fatalMessage ?? (
                $ok
                    ? sprintf('Uploaded %d conversion(s).', $uploaded)
                    : sprintf('Uploaded %d, failed %d.', $uploaded, $failed)
            ),
            'results' => $results,
            'metadata' => [
                'pixel_id' => $pixelId,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $conversion
     * @return array<string, mixed>
     */
    protected function normalizeConversionEvent(array $conversion): array
    {
        $canonical = (string) ($conversion['event_name'] ?? '');
        $providerEvent = $this->mapCanonicalEventToMeta($canonical);

        if ($providerEvent === null) {
            throw new InvalidArgumentException(
                $canonical === ''
                    ? 'Conversion payload is missing event_name.'
                    : "Unsupported conversion event [{$canonical}]."
            );
        }

        $eventTime = $conversion['event_time'] ?? null;
        if ($eventTime instanceof \DateTimeInterface) {
            $eventTime = $eventTime->getTimestamp();
        } elseif (is_string($eventTime) && $eventTime !== '') {
            $eventTime = strtotime($eventTime) ?: time();
        } elseif (! is_int($eventTime) && ! is_float($eventTime)) {
            $eventTime = time();
        }

        $eventId = $this->nullableString($conversion['event_id'] ?? null)
            ?? ('nova_crm_conversion_'.($conversion['conversion_id'] ?? $conversion['id'] ?? uniqid('', true)));

        $userData = [];
        $emailHash = $this->hashUserValue($conversion['email'] ?? null, 'email');
        $phoneHash = $this->hashUserValue($conversion['phone'] ?? null, 'phone');

        if ($emailHash !== null) {
            $userData['em'] = [$emailHash];
        }

        if ($phoneHash !== null) {
            $userData['ph'] = [$phoneHash];
        }

        $fbclid = $this->nullableString($conversion['fbclid'] ?? null);
        if ($fbclid !== null) {
            $userData['fbc'] = sprintf('fb.1.%d.%s', (int) $eventTime, $fbclid);
        }

        $externalLeadId = $this->nullableString($conversion['external_lead_id'] ?? null);
        if ($externalLeadId !== null) {
            $userData['lead_id'] = $externalLeadId;
        }

        if ($userData === []) {
            throw new InvalidArgumentException('Conversion has no usable user identifiers for Meta upload.');
        }

        $event = [
            'event_name' => $providerEvent,
            'event_time' => (int) $eventTime,
            'event_id' => $eventId,
            'action_source' => 'system_generated',
            'user_data' => $userData,
        ];

        $customData = [];
        if (isset($conversion['event_value']) && $conversion['event_value'] !== null && $conversion['event_value'] !== '') {
            $customData['value'] = (float) $conversion['event_value'];
        }
        if (isset($conversion['currency']) && is_string($conversion['currency']) && $conversion['currency'] !== '') {
            $customData['currency'] = strtoupper($conversion['currency']);
        }
        $customData['content_name'] = $canonical;

        if ($customData !== []) {
            $event['custom_data'] = $customData;
        }

        return $event;
    }

    protected function mapCanonicalEventToMeta(string $eventName): ?string
    {
        return match ($eventName) {
            'lead_created' => 'Lead',
            'lead_converted' => 'Lead',
            'customer_created' => 'CompleteRegistration',
            'opportunity_created' => 'InitiateCheckout',
            'opportunity_won' => 'Purchase',
            default => null,
        };
    }

    protected function hashUserValue(mixed $value, string $type): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $normalized = match ($type) {
            'email' => strtolower($normalized),
            'phone' => preg_replace('/\D+/', '', $normalized) ?? '',
            default => $normalized,
        };

        if ($normalized === '') {
            return null;
        }

        return hash('sha256', $normalized);
    }

    public function reportHealth(MarketingProvider $provider): array
    {
        $accessToken = $provider->credential?->access_token;

        if (! $accessToken) {
            return [
                'healthy' => false,
                'status' => MarketingProvider::STATUS_DISCONNECTED,
                'message' => 'Meta provider is not connected.',
                'checked_at' => now()->toIso8601String(),
                'metadata' => [],
            ];
        }

        if ($provider->credential?->isExpired()) {
            return [
                'healthy' => false,
                'status' => MarketingProvider::STATUS_EXPIRED,
                'message' => 'Meta credentials expired.',
                'checked_at' => now()->toIso8601String(),
                'metadata' => [],
            ];
        }

        try {
            $me = $this->graph->getMe($accessToken);

            return [
                'healthy' => true,
                'status' => MarketingProvider::STATUS_CONNECTED,
                'message' => 'Meta credentials are valid.',
                'checked_at' => now()->toIso8601String(),
                'metadata' => [
                    'meta_user_id' => $me['id'] ?? null,
                    'meta_user_name' => $me['name'] ?? null,
                ],
            ];
        } catch (Throwable $e) {
            $expired = $this->looksLikeExpiredToken($e->getMessage());

            return [
                'healthy' => false,
                'status' => $expired
                    ? MarketingProvider::STATUS_EXPIRED
                    : MarketingProvider::STATUS_ERROR,
                'message' => $e->getMessage(),
                'checked_at' => now()->toIso8601String(),
                'metadata' => [],
            ];
        }
    }

    public function discoverAssets(MarketingProvider $provider, array $options = []): array
    {
        $accessToken = $provider->credential?->access_token;

        if (! $accessToken) {
            return [
                'ok' => false,
                'assets' => $this->emptyAssets(),
                'message' => 'Meta provider is not connected.',
                'status' => MarketingProvider::STATUS_DISCONNECTED,
                'discovered_at' => now()->toIso8601String(),
            ];
        }

        if ($provider->credential?->isExpired()) {
            return [
                'ok' => false,
                'assets' => $this->emptyAssets(),
                'message' => 'Meta credentials expired.',
                'status' => MarketingProvider::STATUS_EXPIRED,
                'discovered_at' => now()->toIso8601String(),
            ];
        }

        try {
            $assets = $this->collectAssets($accessToken, $options);

            return [
                'ok' => true,
                'assets' => $assets,
                'message' => null,
                'status' => MarketingProvider::STATUS_CONNECTED,
                'discovered_at' => now()->toIso8601String(),
            ];
        } catch (Throwable $e) {
            $expired = $this->looksLikeExpiredToken($e->getMessage());

            return [
                'ok' => false,
                'assets' => $this->emptyAssets(),
                'message' => $e->getMessage(),
                'status' => $expired
                    ? MarketingProvider::STATUS_EXPIRED
                    : MarketingProvider::STATUS_ERROR,
                'discovered_at' => now()->toIso8601String(),
            ];
        }
    }

    public function validateAssetSelection(MarketingProvider $provider, array $selection): array
    {
        $discovery = $this->discoverAssets($provider, [
            'business_id' => $selection['business_id'] ?? null,
            'page_id' => $selection['page_id'] ?? null,
        ]);

        if (! ($discovery['ok'] ?? false)) {
            throw new InvalidArgumentException(
                $discovery['message'] ?? 'Unable to verify Meta assets for this organization.'
            );
        }

        $assets = $discovery['assets'];
        $businessId = $this->nullableString($selection['business_id'] ?? null);
        $adAccountId = $this->nullableString($selection['ad_account_id'] ?? null);
        $pageId = $this->nullableString($selection['page_id'] ?? null);
        $pixelId = $this->nullableString($selection['pixel_id'] ?? null);
        $leadFormIds = $this->normalizeIdList($selection['lead_form_ids'] ?? []);

        if ($businessId !== null && ! $this->assetExists($assets['businesses'], $businessId)) {
            throw new InvalidArgumentException('Selected Meta Business Manager is not available to this connection.');
        }

        if ($adAccountId !== null) {
            $adAccount = $this->findAsset($assets['ad_accounts'], $adAccountId);
            if ($adAccount === null) {
                throw new InvalidArgumentException('Selected Meta Ad Account is not available to this connection.');
            }
            if ($businessId !== null && ($adAccount['business_id'] ?? null) !== $businessId) {
                throw new InvalidArgumentException('Selected Meta Ad Account does not belong to the selected Business Manager.');
            }
        }

        if ($pageId !== null) {
            $page = $this->findAsset($assets['pages'], $pageId);
            if ($page === null) {
                throw new InvalidArgumentException('Selected Meta Page is not available to this connection.');
            }
            if ($businessId !== null && ($page['business_id'] ?? null) !== null && ($page['business_id'] ?? null) !== $businessId) {
                throw new InvalidArgumentException('Selected Meta Page does not belong to the selected Business Manager.');
            }
        }

        if ($pixelId !== null) {
            $pixel = $this->findAsset($assets['pixels'], $pixelId);
            if ($pixel === null) {
                throw new InvalidArgumentException('Selected Meta Pixel is not available to this connection.');
            }
            if ($businessId !== null && ($pixel['business_id'] ?? null) !== null && ($pixel['business_id'] ?? null) !== $businessId) {
                throw new InvalidArgumentException('Selected Meta Pixel does not belong to the selected Business Manager.');
            }
        }

        foreach ($leadFormIds as $formId) {
            $form = $this->findAsset($assets['lead_forms'], $formId);
            if ($form === null) {
                throw new InvalidArgumentException('Selected Meta Lead Form is not available to this connection.');
            }
            if ($pageId !== null && ($form['page_id'] ?? null) !== $pageId) {
                throw new InvalidArgumentException('Selected Meta Lead Form does not belong to the selected Page.');
            }
        }

        return [
            'business_id' => $businessId,
            'ad_account_id' => $adAccountId,
            'page_id' => $pageId,
            'pixel_id' => $pixelId,
            'lead_form_ids' => $leadFormIds,
        ];
    }

    public function synchronizeLeadForms(MarketingProvider $provider, array $options = []): array
    {
        $accessToken = $provider->credential?->access_token;

        if (! $accessToken) {
            return [
                'ok' => false,
                'forms' => [],
                'synced' => 0,
                'failed' => 0,
                'message' => 'Meta provider is not connected.',
                'status' => MarketingProvider::STATUS_DISCONNECTED,
                'synced_at' => now()->toIso8601String(),
            ];
        }

        if ($provider->credential?->isExpired()) {
            return [
                'ok' => false,
                'forms' => [],
                'synced' => 0,
                'failed' => 0,
                'message' => 'Meta credentials expired.',
                'status' => MarketingProvider::STATUS_EXPIRED,
                'synced_at' => now()->toIso8601String(),
            ];
        }

        $configuration = $provider->credential?->configuration ?? [];
        $selectedFormIds = $this->normalizeIdList(
            $options['lead_form_ids'] ?? ($configuration['lead_form_ids'] ?? [])
        );
        $pageId = $this->nullableString(
            $options['page_id'] ?? ($configuration['page_id'] ?? null)
        );

        if ($selectedFormIds === []) {
            return [
                'ok' => true,
                'forms' => [],
                'synced' => 0,
                'failed' => 0,
                'message' => 'No lead forms selected for synchronization.',
                'status' => MarketingProvider::STATUS_CONNECTED,
                'synced_at' => now()->toIso8601String(),
            ];
        }

        $forms = [];
        $synced = 0;
        $failed = 0;
        $fatalStatus = null;
        $fatalMessage = null;

        foreach ($selectedFormIds as $formId) {
            if ($fatalStatus !== null) {
                $forms[] = [
                    'external_form_id' => $formId,
                    'external_page_id' => $pageId,
                    'sync_ok' => false,
                    'missing' => false,
                    'error' => $fatalMessage,
                ];
                $failed++;

                continue;
            }

            try {
                $payload = $this->graph->getLeadForm($formId, $accessToken);
                $forms[] = $this->normalizeLeadFormDto($payload, $pageId);
                $synced++;
            } catch (Throwable $e) {
                if ($this->looksLikeExpiredToken($e->getMessage()) || $this->looksLikeRevokedPermissions($e->getMessage())) {
                    $fatalStatus = $this->looksLikeExpiredToken($e->getMessage())
                        ? MarketingProvider::STATUS_EXPIRED
                        : MarketingProvider::STATUS_ERROR;
                    $fatalMessage = $e->getMessage();
                    $forms[] = [
                        'external_form_id' => $formId,
                        'external_page_id' => $pageId,
                        'sync_ok' => false,
                        'missing' => false,
                        'error' => $e->getMessage(),
                    ];
                    $failed++;

                    continue;
                }

                $missing = $this->looksLikeMissingObject($e->getMessage());
                $forms[] = [
                    'external_form_id' => $formId,
                    'external_page_id' => $pageId,
                    'sync_ok' => false,
                    'missing' => $missing,
                    'error' => $e->getMessage(),
                ];
                $failed++;
            }
        }

        $partialOk = $synced > 0 && $fatalStatus === null;

        return [
            'ok' => ($fatalStatus === null && $failed === 0) || $partialOk,
            'forms' => $forms,
            'synced' => $synced,
            'failed' => $failed,
            'message' => $fatalMessage
                ?? ($failed > 0
                    ? "Synchronized {$synced} form(s); {$failed} failed."
                    : null),
            'status' => $fatalStatus ?? MarketingProvider::STATUS_CONNECTED,
            'synced_at' => now()->toIso8601String(),
        ];
    }

    public function retrieveLeadEntry(MarketingProvider $provider, string $leadId, array $context = []): array
    {
        $accessToken = $provider->credential?->access_token;

        if (! $accessToken) {
            return [
                'ok' => false,
                'entry' => null,
                'status' => MarketingProvider::STATUS_DISCONNECTED,
                'missing' => false,
                'message' => 'Meta provider is not connected.',
            ];
        }

        if ($provider->credential?->isExpired()) {
            return [
                'ok' => false,
                'entry' => null,
                'status' => MarketingProvider::STATUS_EXPIRED,
                'missing' => false,
                'message' => 'Meta credentials expired.',
            ];
        }

        $leadId = $this->nullableString($leadId);

        if ($leadId === null) {
            return [
                'ok' => false,
                'entry' => null,
                'status' => MarketingProvider::STATUS_CONNECTED,
                'missing' => false,
                'message' => 'Meta webhook is missing a leadgen id.',
            ];
        }

        $fallbackFormId = $this->nullableString($context['form_id'] ?? null) ?? '';
        $pageId = $this->nullableString($context['page_id'] ?? null);

        try {
            $payload = $this->graph->getLead($leadId, $accessToken);
            $entry = $this->normalizeLeadEntryDto($payload, $fallbackFormId, $pageId);

            return [
                'ok' => true,
                'entry' => $entry,
                'status' => MarketingProvider::STATUS_CONNECTED,
                'missing' => false,
                'message' => null,
            ];
        } catch (Throwable $e) {
            if ($this->looksLikeExpiredToken($e->getMessage())) {
                $status = MarketingProvider::STATUS_EXPIRED;
            } elseif ($this->looksLikeRevokedPermissions($e->getMessage())) {
                $status = MarketingProvider::STATUS_ERROR;
            } else {
                $status = MarketingProvider::STATUS_CONNECTED;
            }

            return [
                'ok' => false,
                'entry' => null,
                'status' => $status,
                'missing' => $this->looksLikeMissingObject($e->getMessage()),
                'message' => $e->getMessage(),
            ];
        }
    }

    public function importLeadEntries(MarketingProvider $provider, array $options = []): array
    {
        $accessToken = $provider->credential?->access_token;

        if (! $accessToken) {
            return [
                'ok' => false,
                'entries' => [],
                'fetched' => 0,
                'failed' => 0,
                'message' => 'Meta provider is not connected.',
                'status' => MarketingProvider::STATUS_DISCONNECTED,
                'fetched_at' => now()->toIso8601String(),
            ];
        }

        if ($provider->credential?->isExpired()) {
            return [
                'ok' => false,
                'entries' => [],
                'fetched' => 0,
                'failed' => 0,
                'message' => 'Meta credentials expired.',
                'status' => MarketingProvider::STATUS_EXPIRED,
                'fetched_at' => now()->toIso8601String(),
            ];
        }

        $configuration = $provider->credential?->configuration ?? [];
        $formIds = $this->normalizeIdList(
            $options['lead_form_ids'] ?? ($configuration['lead_form_ids'] ?? [])
        );
        $pageId = $this->nullableString(
            $options['page_id'] ?? ($configuration['page_id'] ?? null)
        );

        if ($formIds === []) {
            return [
                'ok' => true,
                'entries' => [],
                'fetched' => 0,
                'failed' => 0,
                'message' => 'No lead forms selected for import.',
                'status' => MarketingProvider::STATUS_CONNECTED,
                'fetched_at' => now()->toIso8601String(),
            ];
        }

        $entries = [];
        $fetched = 0;
        $failed = 0;
        $fatalStatus = null;
        $fatalMessage = null;

        foreach ($formIds as $formId) {
            if ($fatalStatus !== null) {
                $failed++;

                continue;
            }

            try {
                $leads = $this->graph->listFormLeads($formId, $accessToken);

                foreach ($leads as $leadPayload) {
                    try {
                        $entries[] = $this->normalizeLeadEntryDto($leadPayload, $formId, $pageId);
                        $fetched++;
                    } catch (Throwable $e) {
                        $entries[] = [
                            'external_lead_id' => (string) ($leadPayload['id'] ?? 'unknown'),
                            'external_form_id' => $formId,
                            'external_page_id' => $pageId,
                            'fields' => [],
                            'unmapped_fields' => [],
                            'raw' => is_array($leadPayload) ? $leadPayload : [],
                            'fetch_ok' => false,
                            'error' => $e->getMessage(),
                        ];
                        $failed++;
                    }
                }
            } catch (Throwable $e) {
                if ($this->looksLikeExpiredToken($e->getMessage()) || $this->looksLikeRevokedPermissions($e->getMessage())) {
                    $fatalStatus = $this->looksLikeExpiredToken($e->getMessage())
                        ? MarketingProvider::STATUS_EXPIRED
                        : MarketingProvider::STATUS_ERROR;
                    $fatalMessage = $e->getMessage();
                    $failed++;

                    continue;
                }

                // Deleted / inaccessible form — skip and continue other forms.
                $failed++;
            }
        }

        $partialOk = $fetched > 0 && $fatalStatus === null;

        return [
            'ok' => ($fatalStatus === null && $failed === 0) || $partialOk,
            'entries' => $entries,
            'fetched' => $fetched,
            'failed' => $failed,
            'message' => $fatalMessage
                ?? ($failed > 0
                    ? "Fetched {$fetched} entr(y/ies); {$failed} form/entry failure(s)."
                    : null),
            'status' => $fatalStatus ?? MarketingProvider::STATUS_CONNECTED,
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     external_lead_id: string,
     *     external_form_id: string|null,
     *     external_page_id: string|null,
     *     created_time: string|null,
     *     fields: array<string, string|null>,
     *     unmapped_fields: array<string, mixed>,
     *     raw: array<string, mixed>,
     *     fetch_ok: bool,
     *     error: null
     * }
     */
    protected function normalizeLeadEntryDto(array $payload, string $fallbackFormId, ?string $pageId): array
    {
        $leadId = $this->nullableString($payload['id'] ?? null);

        if ($leadId === null) {
            throw new RuntimeException('Meta lead entry payload missing id.');
        }

        $fieldData = $payload['field_data'] ?? [];
        $fields = [];
        $unmapped = [];

        if (is_array($fieldData)) {
            foreach ($fieldData as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $key = $this->nullableString($row['name'] ?? null);
                if ($key === null) {
                    continue;
                }

                $values = $row['values'] ?? [];
                $value = null;
                if (is_array($values) && $values !== []) {
                    $first = $values[0];
                    $value = is_scalar($first) ? trim((string) $first) : null;
                    $value = $value === '' ? null : $value;
                }

                $normalizedKey = strtolower($key);

                if (in_array($normalizedKey, self::STANDARD_FIELD_KEYS, true)) {
                    $fields[$normalizedKey] = $value;
                } else {
                    $unmapped[$normalizedKey] = $value;
                }
            }
        }

        return [
            'external_lead_id' => $leadId,
            'external_form_id' => $this->nullableString($payload['form_id'] ?? null) ?? $fallbackFormId,
            'external_page_id' => $pageId,
            'created_time' => $this->nullableString($payload['created_time'] ?? null),
            'fields' => $fields,
            'unmapped_fields' => $unmapped,
            'raw' => [
                'id' => $leadId,
                'created_time' => $payload['created_time'] ?? null,
                'ad_id' => $payload['ad_id'] ?? null,
                'ad_name' => $payload['ad_name'] ?? null,
                'form_id' => $payload['form_id'] ?? $fallbackFormId,
                'field_data' => $fieldData,
            ],
            'fetch_ok' => true,
            'error' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     external_form_id: string,
     *     external_page_id: string|null,
     *     name: string|null,
     *     provider_status: string|null,
     *     locale: string|null,
     *     questions: list<array<string, mixed>>,
     *     raw_metadata: array<string, mixed>,
     *     external_updated_at: string|null,
     *     sync_ok: bool,
     *     missing: bool,
     *     error: null
     * }
     */
    protected function normalizeLeadFormDto(array $payload, ?string $fallbackPageId): array
    {
        $formId = $this->nullableString($payload['id'] ?? null);

        if ($formId === null) {
            throw new RuntimeException('Meta lead form payload missing id.');
        }

        $questions = $this->normalizeQuestions($payload['questions'] ?? []);
        $providerStatus = $this->nullableString($payload['status'] ?? null);
        $updatedTime = $this->nullableString($payload['updated_time'] ?? null);

        return [
            'external_form_id' => $formId,
            'external_page_id' => $fallbackPageId,
            'name' => $this->nullableString($payload['name'] ?? null),
            'provider_status' => $providerStatus,
            'locale' => $this->nullableString($payload['locale'] ?? null),
            'questions' => $questions,
            'raw_metadata' => [
                'provider_status' => $providerStatus,
                'updated_time' => $updatedTime,
                'question_count' => count($questions),
            ],
            'external_updated_at' => $updatedTime,
            'sync_ok' => true,
            'missing' => false,
            'error' => null,
        ];
    }

    /**
     * @return list<array{id: string|null, key: string|null, label: string|null, type: string|null, options: mixed}>
     */
    protected function normalizeQuestions(mixed $questions): array
    {
        if (! is_array($questions)) {
            return [];
        }

        $normalized = [];

        foreach ($questions as $question) {
            if (! is_array($question)) {
                continue;
            }

            $key = $this->nullableString($question['key'] ?? null);
            $label = $this->nullableString($question['label'] ?? null) ?? $key;

            $normalized[] = [
                'id' => $this->nullableString($question['id'] ?? null),
                'key' => $key,
                'label' => $label,
                'type' => $this->nullableString($question['type'] ?? null),
                'options' => $question['options'] ?? null,
            ];
        }

        return $normalized;
    }

    protected function looksLikeMissingObject(string $message): bool
    {
        $haystack = strtolower($message);

        return str_contains($haystack, 'does not exist')
            || str_contains($haystack, 'unsupported get request')
            || str_contains($haystack, '(#803)')
            || str_contains($haystack, '(#100)');
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{
     *     businesses: list<array{id: string, name: string|null}>,
     *     ad_accounts: list<array{id: string, name: string|null, business_id?: string|null}>,
     *     pages: list<array{id: string, name: string|null, business_id?: string|null}>,
     *     pixels: list<array{id: string, name: string|null, business_id?: string|null}>,
     *     lead_forms: list<array{id: string, name: string|null, page_id?: string|null}>
     * }
     */
    protected function collectAssets(string $accessToken, array $options): array
    {
        $filterBusinessId = $this->nullableString($options['business_id'] ?? null);
        $filterPageId = $this->nullableString($options['page_id'] ?? null);

        $businesses = [];
        foreach ($this->graph->listBusinesses($accessToken) as $row) {
            $id = $this->nullableString($row['id'] ?? null);
            if ($id === null) {
                continue;
            }
            if ($filterBusinessId !== null && $id !== $filterBusinessId) {
                continue;
            }
            $businesses[] = [
                'id' => $id,
                'name' => $this->nullableString($row['name'] ?? null),
            ];
        }

        $adAccounts = [];
        $pages = [];
        $pixels = [];
        $seenAd = [];
        $seenPage = [];
        $seenPixel = [];

        foreach ($businesses as $business) {
            $businessId = $business['id'];

            foreach ([
                ...$this->safeList(fn () => $this->graph->listOwnedAdAccounts($businessId, $accessToken)),
                ...$this->safeList(fn () => $this->graph->listClientAdAccounts($businessId, $accessToken)),
            ] as $row) {
                $id = $this->normalizeAdAccountId($row);
                if ($id === null || isset($seenAd[$id])) {
                    continue;
                }
                $seenAd[$id] = true;
                $adAccounts[] = [
                    'id' => $id,
                    'name' => $this->nullableString($row['name'] ?? null),
                    'business_id' => $businessId,
                ];
            }

            foreach ([
                ...$this->safeList(fn () => $this->graph->listOwnedPages($businessId, $accessToken)),
                ...$this->safeList(fn () => $this->graph->listClientPages($businessId, $accessToken)),
            ] as $row) {
                $id = $this->nullableString($row['id'] ?? null);
                if ($id === null || isset($seenPage[$id])) {
                    continue;
                }
                $seenPage[$id] = true;
                $pages[] = [
                    'id' => $id,
                    'name' => $this->nullableString($row['name'] ?? null),
                    'business_id' => $businessId,
                ];
            }

            foreach ($this->safeList(fn () => $this->graph->listOwnedPixels($businessId, $accessToken)) as $row) {
                $id = $this->nullableString($row['id'] ?? null);
                if ($id === null || isset($seenPixel[$id])) {
                    continue;
                }
                $seenPixel[$id] = true;
                $pixels[] = [
                    'id' => $id,
                    'name' => $this->nullableString($row['name'] ?? null),
                    'business_id' => $businessId,
                ];
            }
        }

        // Fallback: user pages when business page edges are empty or filtered business has none.
        if ($pages === []) {
            foreach ($this->safeList(fn () => $this->graph->listUserPages($accessToken)) as $row) {
                $id = $this->nullableString($row['id'] ?? null);
                if ($id === null || isset($seenPage[$id])) {
                    continue;
                }
                $seenPage[$id] = true;
                $pages[] = [
                    'id' => $id,
                    'name' => $this->nullableString($row['name'] ?? null),
                    'business_id' => $filterBusinessId,
                ];
            }
        }

        // Ad-account pixels when business-owned pixels are sparse.
        foreach ($adAccounts as $account) {
            foreach ($this->safeList(fn () => $this->graph->listAdAccountPixels($account['id'], $accessToken)) as $row) {
                $id = $this->nullableString($row['id'] ?? null);
                if ($id === null || isset($seenPixel[$id])) {
                    continue;
                }
                $seenPixel[$id] = true;
                $pixels[] = [
                    'id' => $id,
                    'name' => $this->nullableString($row['name'] ?? null),
                    'business_id' => $account['business_id'] ?? null,
                ];
            }
        }

        $leadForms = [];
        $seenForm = [];
        $pagesForForms = $filterPageId !== null
            ? array_values(array_filter($pages, fn (array $page) => $page['id'] === $filterPageId))
            : $pages;

        foreach ($pagesForForms as $page) {
            foreach ($this->safeList(fn () => $this->graph->listLeadForms($page['id'], $accessToken)) as $row) {
                $id = $this->nullableString($row['id'] ?? null);
                if ($id === null || isset($seenForm[$id])) {
                    continue;
                }
                $seenForm[$id] = true;
                $leadForms[] = [
                    'id' => $id,
                    'name' => $this->nullableString($row['name'] ?? null),
                    'page_id' => $page['id'],
                ];
            }
        }

        return [
            'businesses' => $businesses,
            'ad_accounts' => $adAccounts,
            'pages' => $pages,
            'pixels' => $pixels,
            'lead_forms' => $leadForms,
        ];
    }

    /**
     * @return array{
     *     businesses: list<array{id: string, name: string|null}>,
     *     ad_accounts: list<array{id: string, name: string|null, business_id?: string|null}>,
     *     pages: list<array{id: string, name: string|null, business_id?: string|null}>,
     *     pixels: list<array{id: string, name: string|null, business_id?: string|null}>,
     *     lead_forms: list<array{id: string, name: string|null, page_id?: string|null}>
     * }
     */
    protected function emptyAssets(): array
    {
        return [
            'businesses' => [],
            'ad_accounts' => [],
            'pages' => [],
            'pixels' => [],
            'lead_forms' => [],
        ];
    }

    /**
     * Soft-fail individual edges so one inaccessible object does not abort discovery.
     *
     * @param  callable(): list<array<string, mixed>>  $callback
     * @return list<array<string, mixed>>
     */
    protected function safeList(callable $callback): array
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            if ($this->looksLikeExpiredToken($e->getMessage()) || $this->looksLikeRevokedPermissions($e->getMessage())) {
                throw $e;
            }

            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function normalizeAdAccountId(array $row): ?string
    {
        $id = $this->nullableString($row['id'] ?? null);
        if ($id !== null) {
            return str_starts_with($id, 'act_') ? $id : 'act_'.$id;
        }

        $accountId = $this->nullableString($row['account_id'] ?? null);

        return $accountId !== null
            ? (str_starts_with($accountId, 'act_') ? $accountId : 'act_'.$accountId)
            : null;
    }

    /**
     * @param  list<array{id: string, name: string|null}>  $assets
     */
    protected function assetExists(array $assets, string $id): bool
    {
        return $this->findAsset($assets, $id) !== null;
    }

    /**
     * @param  list<array<string, mixed>>  $assets
     * @return array<string, mixed>|null
     */
    protected function findAsset(array $assets, string $id): ?array
    {
        foreach ($assets as $asset) {
            $assetId = $this->nullableString($asset['id'] ?? null);
            if ($assetId === $id) {
                return $asset;
            }
            // Ad accounts may be compared with/without act_ prefix.
            if (str_starts_with($id, 'act_') && $assetId === substr($id, 4)) {
                return $asset;
            }
            if (str_starts_with((string) $assetId, 'act_') && substr((string) $assetId, 4) === $id) {
                return $asset;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function normalizeIdList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $item) {
            $id = $this->nullableString($item);
            if ($id !== null) {
                $ids[$id] = true;
            }
        }

        return array_keys($ids);
    }

    protected function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    protected function looksLikeRevokedPermissions(string $message): bool
    {
        $haystack = strtolower($message);

        return str_contains($haystack, 'permission')
            || str_contains($haystack, 'oauth exception')
            || str_contains($haystack, '(#10)')
            || str_contains($haystack, '(#200)');
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{authorization_url: string, status: string, metadata: array<string, mixed>}
     */
    protected function beginAuthorization(MarketingProvider $provider, array $context): array
    {
        $this->assertConfigured();

        $state = $this->makeState($provider);
        $redirectUri = $context['redirect_uri'] ?? $this->redirectUri();
        $scopes = $context['scopes'] ?? $this->scopes();

        $query = http_build_query([
            'client_id' => config('marketing.providers.meta.client_id'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'response_type' => 'code',
            'scope' => implode(',', $scopes),
        ]);

        $dialogBase = rtrim((string) config('marketing.providers.meta.oauth_dialog_url'), '/');
        $version = trim((string) config('marketing.providers.meta.api_version'), '/');

        return [
            'authorization_url' => $dialogBase.'/'.$version.'/dialog/oauth?'.$query,
            'metadata' => [
                'state' => $state,
                'redirect_uri' => $redirectUri,
                'scopes' => $scopes,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{credentials: array<string, mixed>, status: string, metadata: array<string, mixed>}
     */
    protected function completeAuthorization(MarketingProvider $provider, array $context): array
    {
        $this->assertConfigured();

        $code = $context['code'] ?? null;

        if (! is_string($code) || $code === '') {
            throw new RuntimeException('Meta OAuth callback is missing authorization code.');
        }

        $state = $context['state'] ?? null;

        if (! is_string($state) || $state === '') {
            throw new RuntimeException('Meta OAuth callback is missing state.');
        }

        $this->assertValidState($provider, $state);

        $redirectUri = $context['redirect_uri'] ?? $this->redirectUri();
        $shortLived = $this->graph->exchangeCodeForToken($code, $redirectUri);
        $shortToken = $shortLived['access_token'] ?? null;

        if (! is_string($shortToken) || $shortToken === '') {
            throw new RuntimeException('Meta OAuth token exchange returned no access_token.');
        }

        $longLived = $this->graph->exchangeForLongLivedToken($shortToken);
        $credentials = $this->normalizeCredentials(
            $longLived,
            $context['scopes'] ?? $this->scopes(),
        );

        $me = $this->graph->getMe($credentials['access_token']);
        $credentials['external_account_id'] = isset($me['id']) ? (string) $me['id'] : null;
        $credentials['metadata'] = array_filter([
            'meta_user_id' => $me['id'] ?? null,
            'meta_user_name' => $me['name'] ?? null,
            'token_type_raw' => $longLived['token_type'] ?? ($shortLived['token_type'] ?? null),
        ]);

        return [
            'credentials' => $credentials,
            'status' => MarketingProvider::STATUS_CONNECTED,
            'metadata' => [
                'meta_user_id' => $me['id'] ?? null,
                'meta_user_name' => $me['name'] ?? null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $tokenPayload
     * @param  list<string>|null  $scopes
     * @return array{access_token: string, refresh_token: null, token_type: string, scopes: list<string>, expires_at: Carbon|null, metadata?: array<string, mixed>}
     */
    protected function normalizeCredentials(array $tokenPayload, ?array $scopes = null): array
    {
        $accessToken = $tokenPayload['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Meta token payload missing access_token.');
        }

        $expiresAt = null;

        if (isset($tokenPayload['expires_in']) && is_numeric($tokenPayload['expires_in'])) {
            $expiresAt = now()->addSeconds((int) $tokenPayload['expires_in']);
        }

        return [
            // Meta long-lived user tokens do not use a separate refresh_token;
            // the access token is re-exchanged via fb_exchange_token.
            'access_token' => $accessToken,
            'refresh_token' => null,
            'token_type' => (string) ($tokenPayload['token_type'] ?? 'bearer'),
            'scopes' => $scopes ?? $this->scopes(),
            'expires_at' => $expiresAt,
        ];
    }

    protected function makeState(MarketingProvider $provider): string
    {
        return Crypt::encryptString(json_encode([
            'provider_id' => $provider->id,
            'organization_id' => $provider->organization_id,
            'slug' => self::SLUG,
            'exp' => now()->addMinutes(15)->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    protected function assertValidState(MarketingProvider $provider, string $state): void
    {
        try {
            $payload = json_decode(Crypt::decryptString($state), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new RuntimeException('Meta OAuth state is invalid.', 0, $e);
        }

        if (! is_array($payload)) {
            throw new RuntimeException('Meta OAuth state is invalid.');
        }

        if (($payload['provider_id'] ?? null) !== $provider->id
            || ($payload['organization_id'] ?? null) !== $provider->organization_id
            || ($payload['slug'] ?? null) !== self::SLUG) {
            throw new RuntimeException('Meta OAuth state does not match provider.');
        }

        if (($payload['exp'] ?? 0) < now()->timestamp) {
            throw new RuntimeException('Meta OAuth state has expired.');
        }
    }

    protected function redirectUri(): string
    {
        $configured = config('marketing.providers.meta.redirect_uri');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return route('marketing.providers.callback', ['provider' => self::SLUG], absolute: true);
    }

    /**
     * @return list<string>
     */
    protected function scopes(): array
    {
        $scopes = config('marketing.providers.meta.scopes', []);

        return array_values(array_filter(array_map('strval', is_array($scopes) ? $scopes : [])));
    }

    protected function assertConfigured(): void
    {
        if (! config('marketing.providers.meta.client_id') || ! config('marketing.providers.meta.client_secret')) {
            throw new RuntimeException('Meta OAuth is not configured (META_APP_ID / META_APP_SECRET).');
        }
    }

    protected function looksLikeExpiredToken(string $message): bool
    {
        $haystack = strtolower($message);

        return str_contains($haystack, 'session has expired')
            || str_contains($haystack, 'error validating access token')
            || str_contains($haystack, 'expired')
            || str_contains($haystack, '(#190)');
    }
}
