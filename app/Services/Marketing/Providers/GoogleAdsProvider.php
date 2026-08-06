<?php

namespace App\Services\Marketing\Providers;

use App\Contracts\MarketingProviderAssetDiscoveryInterface;
use App\Contracts\MarketingProviderInterface;
use App\Models\MarketingProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Google Ads provider adapter.
 *
 * OAuth, credential health, read-only asset discovery, and manual offline
 * conversion uploads. Campaign discovery, synchronization, and reporting
 * remain intentionally unsupported.
 */
class GoogleAdsProvider implements MarketingProviderAssetDiscoveryInterface, MarketingProviderInterface
{
    public const SLUG = 'google_ads';

    public function __construct(
        protected GoogleAdsClient $client,
    ) {}

    public function slug(): string
    {
        return self::SLUG;
    }

    public function displayName(): string
    {
        return (string) (config('marketing.providers.catalog.google_ads.name') ?? 'Google Ads');
    }

    public function capabilities(): array
    {
        return ['oauth', 'token_refresh', 'asset_discovery', 'offline_conversions'];
    }

    public function authorize(MarketingProvider $provider, array $context = []): array
    {
        $phase = $context['phase'] ?? (isset($context['code']) ? 'callback' : 'start');

        return match ($phase) {
            'start' => $this->beginAuthorization($provider, $context),
            'callback' => $this->completeAuthorization($provider, $context),
            default => throw new RuntimeException("Unsupported Google Ads authorize phase [{$phase}]."),
        };
    }

    public function refreshCredentials(MarketingProvider $provider): array
    {
        $refreshToken = $provider->credential?->refresh_token;

        if (! is_string($refreshToken) || $refreshToken === '') {
            throw new RuntimeException('Google Ads provider has no refresh token.');
        }

        $token = $this->client->refreshAccessToken($refreshToken);

        return $this->normalizeCredentials(
            $token,
            $refreshToken,
            $provider->credential?->scopes,
        );
    }

    public function revoke(MarketingProvider $provider): void
    {
        $token = $provider->credential?->refresh_token
            ?: $provider->credential?->access_token;

        if (! is_string($token) || $token === '') {
            return;
        }

        try {
            $this->client->revokeToken($token);
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
            'message' => 'Not yet implemented: Google Ads synchronization.',
        ];
    }

    public function receiveWebhook(MarketingProvider $provider, array $payload, array $headers = []): array
    {
        return [
            'ok' => false,
            'event' => null,
            'normalized' => null,
            'message' => 'Not supported: Google Ads webhooks.',
        ];
    }

    public function uploadConversions(MarketingProvider $provider, array $conversions): array
    {
        $accessToken = $provider->credential?->access_token;
        $configuration = $provider->credential?->configuration ?? [];
        $customerId = $this->nullableString($configuration['customer_id'] ?? null);
        $selectedActionIds = $this->normalizeIdList($configuration['conversion_action_ids'] ?? []);

        if (! is_string($accessToken) || $accessToken === '') {
            return $this->uploadFailure(
                $conversions,
                MarketingProvider::STATUS_DISCONNECTED,
                'Google Ads provider is not connected.',
            );
        }

        if ($provider->credential?->isExpired()) {
            return $this->uploadFailure(
                $conversions,
                MarketingProvider::STATUS_EXPIRED,
                'Google Ads credentials expired.',
            );
        }

        if ($customerId === null) {
            return $this->uploadFailure(
                $conversions,
                MarketingProvider::STATUS_ERROR,
                'Google Ads customer_id is not configured for this organization.',
            );
        }

        if ($selectedActionIds === []) {
            return $this->uploadFailure(
                $conversions,
                MarketingProvider::STATUS_ERROR,
                'No Google Ads conversion actions are selected for this organization.',
            );
        }

        try {
            $customer = $this->client->getCustomer($customerId, $accessToken);
            $timeZone = $this->nullableString($customer['timeZone'] ?? null) ?? 'UTC';
            $remoteActions = $this->client->listConversionActions($customerId, $accessToken);
        } catch (Throwable $e) {
            return $this->uploadFailure(
                $conversions,
                $this->looksLikeInvalidToken($e->getMessage())
                    ? MarketingProvider::STATUS_EXPIRED
                    : MarketingProvider::STATUS_ERROR,
                $e->getMessage(),
            );
        }

        $actions = $this->selectedUploadActions($remoteActions, $selectedActionIds);
        $prepared = [];
        $preparedConversionIds = [];
        $preparedActionIds = [];
        $results = [];

        foreach ($conversions as $conversion) {
            $conversionId = is_array($conversion)
                ? ($conversion['conversion_id'] ?? $conversion['id'] ?? null)
                : null;

            if (! is_array($conversion)) {
                $results[] = [
                    'ok' => false,
                    'conversion_id' => $conversionId,
                    'message' => 'Conversion payload must be an array.',
                ];

                continue;
            }

            try {
                $action = $this->resolveConversionAction($conversion, $actions, $selectedActionIds);
                $prepared[] = $this->normalizeOfflineConversion(
                    $conversion,
                    $customerId,
                    $action['id'],
                    $timeZone,
                );
                $preparedConversionIds[] = $conversionId;
                $preparedActionIds[] = $action['id'];
            } catch (Throwable $e) {
                $results[] = [
                    'ok' => false,
                    'conversion_id' => $conversionId,
                    'message' => $e->getMessage(),
                ];
            }
        }

        if ($prepared !== []) {
            try {
                $upload = $this->client->uploadOfflineConversions(
                    $customerId,
                    $accessToken,
                    $prepared,
                );

                foreach ($upload['results'] as $index => $row) {
                    $conversionId = $preparedConversionIds[$index] ?? null;
                    $actionId = $preparedActionIds[$index] ?? null;

                    if ($row['ok']) {
                        $results[] = [
                            'ok' => true,
                            'conversion_id' => $conversionId,
                            'external_event_id' => $prepared[$index]['orderId'] ?? null,
                            'provider_event_name' => $actionId,
                            'google_job_id' => $upload['job_id'],
                            'message' => null,
                        ];

                        continue;
                    }

                    $results[] = [
                        'ok' => false,
                        'conversion_id' => $conversionId,
                        'provider_event_name' => $actionId,
                        'message' => $row['message'],
                    ];
                }
            } catch (Throwable $e) {
                foreach ($preparedConversionIds as $index => $conversionId) {
                    $results[] = [
                        'ok' => false,
                        'conversion_id' => $conversionId,
                        'provider_event_name' => $preparedActionIds[$index] ?? null,
                        'message' => $e->getMessage(),
                    ];
                }

                $fatalStatus = $this->looksLikeInvalidToken($e->getMessage())
                    ? MarketingProvider::STATUS_EXPIRED
                    : MarketingProvider::STATUS_ERROR;

                return $this->summarizeUploadResults(
                    $results,
                    $customerId,
                    $fatalStatus,
                    $e->getMessage(),
                );
            }
        }

        return $this->summarizeUploadResults($results, $customerId);
    }

    /**
     * @param  list<array<string, mixed>>  $conversions
     * @return array<string, mixed>
     */
    protected function uploadFailure(array $conversions, string $status, string $message): array
    {
        $results = [];
        foreach ($conversions as $conversion) {
            $results[] = [
                'ok' => false,
                'conversion_id' => is_array($conversion)
                    ? ($conversion['conversion_id'] ?? $conversion['id'] ?? null)
                    : null,
                'message' => $message,
            ];
        }

        return [
            'ok' => false,
            'uploaded' => 0,
            'failed' => count($results),
            'skipped' => 0,
            'status' => $status,
            'message' => $message,
            'results' => $results,
            'metadata' => [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $results
     * @return array<string, mixed>
     */
    protected function summarizeUploadResults(
        array $results,
        string $customerId,
        ?string $status = null,
        ?string $message = null,
    ): array {
        $uploaded = count(array_filter($results, fn (array $row) => ($row['ok'] ?? false) === true));
        $failed = count($results) - $uploaded;
        $firstError = null;

        if ($uploaded === 0 && $failed > 0) {
            foreach ($results as $result) {
                $firstError = $this->nullableString($result['message'] ?? null);
                if ($firstError !== null) {
                    break;
                }
            }
        }

        return [
            'ok' => $failed === 0 || $uploaded > 0,
            'uploaded' => $uploaded,
            'failed' => $failed,
            'skipped' => 0,
            'status' => $status,
            'message' => $message
                ?? $firstError
                ?? ($failed > 0
                    ? sprintf('Uploaded %d, failed %d.', $uploaded, $failed)
                    : sprintf('Uploaded %d conversion(s).', $uploaded)),
            'results' => $results,
            'metadata' => [
                'customer_id' => $customerId,
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $remoteActions
     * @param  list<string>  $selectedActionIds
     * @return list<array{id: string, category: string|null}>
     */
    protected function selectedUploadActions(array $remoteActions, array $selectedActionIds): array
    {
        $actions = [];

        foreach ($remoteActions as $remote) {
            $id = $this->nullableString($remote['id'] ?? null);
            if ($id === null || ! in_array($id, $selectedActionIds, true)) {
                continue;
            }

            $status = strtoupper($this->nullableString($remote['status'] ?? null) ?? '');
            $type = strtoupper($this->nullableString($remote['type'] ?? null) ?? '');

            if ($status !== 'ENABLED' || $type !== 'UPLOAD_CLICKS') {
                continue;
            }

            $actions[] = [
                'id' => $id,
                'category' => $this->nullableString($remote['category'] ?? null),
            ];
        }

        return $actions;
    }

    /**
     * @param  array<string, mixed>  $conversion
     * @param  list<array{id: string, category: string|null}>  $actions
     * @param  list<string>  $selectedActionIds
     * @return array{id: string, category: string|null}
     */
    protected function resolveConversionAction(
        array $conversion,
        array $actions,
        array $selectedActionIds,
    ): array {
        $eventName = $this->nullableString($conversion['event_name'] ?? null);
        $preferredCategories = $this->conversionActionCategories($eventName);

        if ($preferredCategories === null) {
            throw new InvalidArgumentException(
                $eventName === null
                    ? 'Conversion payload is missing event_name.'
                    : "Unsupported conversion event [{$eventName}]."
            );
        }

        foreach ($preferredCategories as $category) {
            foreach ($actions as $action) {
                if (strtoupper((string) $action['category']) === $category) {
                    return $action;
                }
            }
        }

        if (count($actions) === 1) {
            return $actions[0];
        }

        if ($actions === []) {
            throw new InvalidArgumentException(
                'Selected Google Ads conversion action is removed, inactive, or not an offline click conversion action.'
            );
        }

        throw new InvalidArgumentException(sprintf(
            'No selected Google Ads conversion action matches [%s]; selected action IDs: %s.',
            $eventName,
            implode(', ', $selectedActionIds),
        ));
    }

    /**
     * @return list<string>|null
     */
    protected function conversionActionCategories(?string $eventName): ?array
    {
        return match ($eventName) {
            'lead_created' => ['SUBMIT_LEAD_FORM', 'LEAD', 'IMPORTED_LEAD', 'CONTACT'],
            'lead_converted' => ['CONVERTED_LEAD', 'QUALIFIED_LEAD', 'LEAD'],
            'customer_created' => ['SIGNUP'],
            'opportunity_created' => ['BEGIN_CHECKOUT', 'REQUEST_QUOTE'],
            'opportunity_won' => ['PURCHASE', 'STORE_SALE'],
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $conversion
     * @return array<string, mixed>
     */
    protected function normalizeOfflineConversion(
        array $conversion,
        string $customerId,
        string $actionId,
        string $timeZone,
    ): array {
        $eventTime = $conversion['event_time'] ?? null;
        if ($eventTime instanceof \DateTimeInterface) {
            $occurredAt = Carbon::instance($eventTime);
        } elseif (is_numeric($eventTime)) {
            $occurredAt = Carbon::createFromTimestamp((int) $eventTime, 'UTC');
        } elseif (is_string($eventTime) && trim($eventTime) !== '') {
            $occurredAt = Carbon::parse($eventTime);
        } else {
            throw new InvalidArgumentException('Conversion payload is missing event_time.');
        }

        try {
            $occurredAt = $occurredAt->setTimezone($timeZone);
        } catch (Throwable) {
            throw new InvalidArgumentException(
                "Google Ads customer time zone [{$timeZone}] is invalid."
            );
        }

        $identifiers = [];
        $email = $this->hashUserValue($conversion['email'] ?? null, 'email');
        $phone = $this->hashUserValue($conversion['phone'] ?? null, 'phone');

        if ($email !== null) {
            $identifiers[] = ['hashedEmail' => $email];
        }
        if ($phone !== null) {
            $identifiers[] = ['hashedPhoneNumber' => $phone];
        }

        $gclid = $this->nullableString($conversion['gclid'] ?? null);
        $gbraid = $this->nullableString($conversion['gbraid'] ?? null);
        $wbraid = $this->nullableString($conversion['wbraid'] ?? null);

        if ($identifiers === [] && $gclid === null && $gbraid === null && $wbraid === null) {
            throw new InvalidArgumentException(
                'Conversion has no usable Google click ID or enhanced-conversion user identifier.'
            );
        }

        $payload = [
            'conversionAction' => sprintf(
                'customers/%s/conversionActions/%s',
                $customerId,
                $actionId,
            ),
            'conversionDateTime' => $occurredAt->format('Y-m-d H:i:sP'),
            'orderId' => $this->nullableString($conversion['event_id'] ?? null)
                ?? ('nova_crm_conversion_'.($conversion['conversion_id'] ?? $conversion['id'] ?? uniqid('', true))),
        ];

        if ($identifiers !== []) {
            $payload['userIdentifiers'] = $identifiers;
        }
        if ($gclid !== null) {
            $payload['gclid'] = $gclid;
        } elseif ($gbraid !== null) {
            $payload['gbraid'] = $gbraid;
        } elseif ($wbraid !== null) {
            $payload['wbraid'] = $wbraid;
        }
        if (isset($conversion['event_value']) && is_numeric($conversion['event_value'])) {
            $payload['conversionValue'] = (float) $conversion['event_value'];
        }
        if (is_string($conversion['currency'] ?? null) && trim($conversion['currency']) !== '') {
            $payload['currencyCode'] = strtoupper(trim($conversion['currency']));
        }

        return $payload;
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

        return $normalized !== '' ? hash('sha256', $normalized) : null;
    }

    public function reportHealth(MarketingProvider $provider): array
    {
        return $this->healthCheck($provider);
    }

    /**
     * Validate the token and call a read-only Google Ads reachability endpoint.
     *
     * @return array<string, mixed>
     */
    public function healthCheck(MarketingProvider $provider): array
    {
        $credential = $provider->credential;
        $accessToken = $credential?->access_token;
        $refreshCapable = is_string($credential?->refresh_token)
            && $credential->refresh_token !== '';

        if (! is_string($accessToken) || $accessToken === '') {
            return $this->healthFailure(
                MarketingProvider::STATUS_DISCONNECTED,
                'Google Ads provider is not connected.',
                $refreshCapable,
            );
        }

        if ($credential?->isExpired()) {
            return $this->healthFailure(
                MarketingProvider::STATUS_EXPIRED,
                'Google Ads credentials expired.',
                $refreshCapable,
            );
        }

        if (! $refreshCapable) {
            return $this->healthFailure(
                MarketingProvider::STATUS_ERROR,
                'Google Ads refresh token is unavailable; reconnect with offline consent.',
                false,
            );
        }

        try {
            $tokenInfo = $this->client->tokenInfo($accessToken);
            $api = $this->client->listAccessibleCustomers($accessToken);

            return [
                'healthy' => true,
                'status' => MarketingProvider::STATUS_CONNECTED,
                'message' => 'Google Ads credentials and API access are valid.',
                'checked_at' => now()->toIso8601String(),
                'metadata' => [
                    'google_subject' => $tokenInfo['sub'] ?? null,
                    'refresh_capable' => true,
                    'api_reachable' => true,
                    'accessible_customer_count' => count(
                        is_array($api['resourceNames'] ?? null) ? $api['resourceNames'] : []
                    ),
                ],
            ];
        } catch (Throwable $e) {
            $expired = $this->looksLikeInvalidToken($e->getMessage());

            return $this->healthFailure(
                $expired ? MarketingProvider::STATUS_EXPIRED : MarketingProvider::STATUS_ERROR,
                $e->getMessage(),
                true,
            );
        }
    }

    /**
     * Discover customer accounts and conversion actions (read-only).
     *
     * @param  array<string, mixed>  $options  e.g. customer_id filter
     * @return array<string, mixed>
     */
    public function discoverAssets(MarketingProvider $provider, array $options = []): array
    {
        $accessToken = $provider->credential?->access_token;

        if (! is_string($accessToken) || $accessToken === '') {
            return [
                'ok' => false,
                'assets' => $this->emptyAssets(),
                'message' => 'Google Ads provider is not connected.',
                'status' => MarketingProvider::STATUS_DISCONNECTED,
                'discovered_at' => now()->toIso8601String(),
            ];
        }

        if ($provider->credential?->isExpired()) {
            return [
                'ok' => false,
                'assets' => $this->emptyAssets(),
                'message' => 'Google Ads credentials expired.',
                'status' => MarketingProvider::STATUS_EXPIRED,
                'discovered_at' => now()->toIso8601String(),
            ];
        }

        try {
            $assets = $this->collectAssets($accessToken, $options, $provider->credential?->configuration ?? []);

            return [
                'ok' => true,
                'assets' => $assets,
                'message' => null,
                'status' => MarketingProvider::STATUS_CONNECTED,
                'discovered_at' => now()->toIso8601String(),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'assets' => $this->emptyAssets(),
                'message' => $e->getMessage(),
                'status' => $this->looksLikeInvalidToken($e->getMessage())
                    ? MarketingProvider::STATUS_EXPIRED
                    : MarketingProvider::STATUS_ERROR,
                'discovered_at' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Verify client-submitted selections against live Google Ads assets.
     *
     * @param  array<string, mixed>  $selection
     * @return array{customer_id: string|null, conversion_action_ids: list<string>}
     */
    public function validateAssetSelection(MarketingProvider $provider, array $selection): array
    {
        $customerId = $this->nullableString($selection['customer_id'] ?? null);
        $conversionActionIds = $this->normalizeIdList($selection['conversion_action_ids'] ?? []);

        $discovery = $this->discoverAssets($provider, [
            'customer_id' => $customerId,
        ]);

        if (! ($discovery['ok'] ?? false)) {
            throw new InvalidArgumentException(
                $discovery['message'] ?? 'Unable to verify Google Ads assets for this organization.'
            );
        }

        $assets = $discovery['assets'];

        if ($customerId !== null && ! $this->customerExists($assets['customers'], $customerId)) {
            throw new InvalidArgumentException(
                'Selected Google Ads customer account is not accessible to this connection.'
            );
        }

        if ($conversionActionIds !== [] && $customerId === null) {
            throw new InvalidArgumentException(
                'Select a Google Ads customer account before selecting conversion actions.'
            );
        }

        foreach ($conversionActionIds as $actionId) {
            $action = $this->findConversionAction($assets['conversion_actions'], $actionId, $customerId);

            if ($action === null || ($action['missing'] ?? false)) {
                throw new InvalidArgumentException(
                    'Selected Google Ads conversion action is not available to this connection.'
                );
            }
        }

        return [
            'customer_id' => $customerId,
            'conversion_action_ids' => $conversionActionIds,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $configuration
     * @return array{
     *     customers: list<array<string, mixed>>,
     *     conversion_actions: list<array<string, mixed>>
     * }
     */
    protected function collectAssets(string $accessToken, array $options, array $configuration): array
    {
        $filterCustomerId = $this->nullableString($options['customer_id'] ?? null);

        $customers = [];
        foreach ($this->client->listCustomers($accessToken) as $customerId) {
            if ($filterCustomerId !== null && $customerId !== $filterCustomerId) {
                continue;
            }

            $customer = $this->safeCall(fn () => $this->client->getCustomer($customerId, $accessToken));

            $customers[] = [
                'id' => $customerId,
                'descriptive_name' => $this->nullableString($customer['descriptiveName'] ?? null),
                'currency_code' => $this->nullableString($customer['currencyCode'] ?? null),
                'time_zone' => $this->nullableString($customer['timeZone'] ?? null),
                'manager' => (bool) ($customer['manager'] ?? false),
                'accessible' => $customer !== null,
            ];
        }

        $conversionActions = [];
        $seenActions = [];

        foreach ($customers as $customer) {
            // Manager accounts hold no uploadable conversion actions of their own;
            // inaccessible accounts already soft-failed above.
            if ($customer['manager'] || ! $customer['accessible']) {
                continue;
            }

            $actions = $this->safeCall(
                fn () => $this->client->listConversionActions($customer['id'], $accessToken)
            ) ?? [];

            foreach ($actions as $action) {
                $actionId = $this->nullableString($action['id'] ?? null);
                if ($actionId === null || isset($seenActions[$customer['id'].':'.$actionId])) {
                    continue;
                }
                $seenActions[$customer['id'].':'.$actionId] = true;

                $status = $this->nullableString($action['status'] ?? null);

                $conversionActions[] = [
                    'id' => $actionId,
                    'customer_id' => $customer['id'],
                    'name' => $this->nullableString($action['name'] ?? null),
                    'category' => $this->nullableString($action['category'] ?? null),
                    'type' => $this->nullableString($action['type'] ?? null),
                    'status' => $status,
                    'primary_for_goal' => (bool) ($action['primaryForGoal'] ?? false),
                    'active' => $status === null || strtoupper($status) === 'ENABLED',
                    'missing' => false,
                ];
            }
        }

        // Previously selected conversion actions that disappeared remotely are
        // surfaced as inactive; saved configuration is never silently deleted.
        $selectedCustomerId = $this->nullableString($configuration['customer_id'] ?? null);
        foreach ($this->normalizeIdList($configuration['conversion_action_ids'] ?? []) as $selectedId) {
            if ($this->findConversionAction($conversionActions, $selectedId, $selectedCustomerId) !== null) {
                continue;
            }

            $conversionActions[] = [
                'id' => $selectedId,
                'customer_id' => $selectedCustomerId,
                'name' => null,
                'category' => null,
                'type' => null,
                'status' => 'REMOVED',
                'primary_for_goal' => false,
                'active' => false,
                'missing' => true,
            ];
        }

        return [
            'customers' => $customers,
            'conversion_actions' => $conversionActions,
        ];
    }

    /**
     * @return array{customers: list<array<string, mixed>>, conversion_actions: list<array<string, mixed>>}
     */
    protected function emptyAssets(): array
    {
        return [
            'customers' => [],
            'conversion_actions' => [],
        ];
    }

    /**
     * Soft-fail individual account queries so one inaccessible customer does not
     * abort discovery. Token expiry / revocation still surfaces to the caller.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T|null
     */
    protected function safeCall(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            if ($this->looksLikeInvalidToken($e->getMessage())) {
                throw $e;
            }

            return null;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $customers
     */
    protected function customerExists(array $customers, string $customerId): bool
    {
        foreach ($customers as $customer) {
            if (($customer['id'] ?? null) === $customerId && ($customer['accessible'] ?? true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $actions
     * @return array<string, mixed>|null
     */
    protected function findConversionAction(array $actions, string $actionId, ?string $customerId): ?array
    {
        foreach ($actions as $action) {
            if (($action['id'] ?? null) !== $actionId) {
                continue;
            }

            if ($customerId !== null && ($action['customer_id'] ?? null) !== $customerId) {
                continue;
            }

            return $action;
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
            if ($id !== null && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function beginAuthorization(MarketingProvider $provider, array $context): array
    {
        $this->assertConfigured();

        $state = $this->makeState($provider);
        $redirectUri = $context['redirect_uri'] ?? $this->redirectUri();
        $scopes = $context['scopes'] ?? $this->scopes();

        return [
            'authorization_url' => $this->client->authorizationUrl($redirectUri, $state, $scopes),
            'metadata' => [
                'state' => $state,
                'redirect_uri' => $redirectUri,
                'scopes' => $scopes,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function completeAuthorization(MarketingProvider $provider, array $context): array
    {
        $this->assertConfigured();

        $code = $context['code'] ?? null;
        if (! is_string($code) || $code === '') {
            throw new RuntimeException('Google Ads OAuth callback is missing authorization code.');
        }

        $state = $context['state'] ?? null;
        if (! is_string($state) || $state === '') {
            throw new RuntimeException('Google Ads OAuth callback is missing state.');
        }

        $this->assertValidState($provider, $state);

        $redirectUri = $context['redirect_uri'] ?? $this->redirectUri();
        $token = $this->client->exchangeCodeForToken($code, $redirectUri);
        $refreshToken = $token['refresh_token'] ?? $provider->credential?->refresh_token;

        if (! is_string($refreshToken) || $refreshToken === '') {
            throw new RuntimeException(
                'Google OAuth did not return a refresh token. Reconnect and grant offline consent.'
            );
        }

        $credentials = $this->normalizeCredentials(
            $token,
            $refreshToken,
            $context['scopes'] ?? $this->scopes(),
        );
        $tokenInfo = $this->client->tokenInfo($credentials['access_token']);
        $subject = $this->nullableString($tokenInfo['sub'] ?? null);
        $email = $this->nullableString($tokenInfo['email'] ?? null);
        $configuration = $provider->credential?->configuration ?? [];
        $configuration['customer_id'] = $configuration['customer_id'] ?? null;

        $credentials['external_account_id'] = $subject ?? $email;
        $credentials['configuration'] = $configuration;
        $credentials['metadata'] = array_filter([
            'google_subject' => $subject,
            'google_email' => $email,
            'token_type_raw' => $token['token_type'] ?? null,
        ], static fn (mixed $value): bool => $value !== null);

        return [
            'credentials' => $credentials,
            'status' => MarketingProvider::STATUS_CONNECTED,
            'metadata' => [
                'google_subject' => $subject,
                'google_email' => $email,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $token
     * @param  list<string>|null  $scopes
     * @return array<string, mixed>
     */
    protected function normalizeCredentials(array $token, string $refreshToken, ?array $scopes = null): array
    {
        $accessToken = $token['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Google OAuth token payload missing access_token.');
        }

        $expiresAt = null;
        if (isset($token['expires_in']) && is_numeric($token['expires_in'])) {
            $expiresAt = now()->addSeconds((int) $token['expires_in']);
        }

        $tokenScopes = $this->normalizeScopes($token['scope'] ?? null);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => (string) ($token['token_type'] ?? 'Bearer'),
            'scopes' => $tokenScopes !== [] ? $tokenScopes : ($scopes ?? $this->scopes()),
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
            throw new RuntimeException('Google Ads OAuth state is invalid.', 0, $e);
        }

        if (! is_array($payload)) {
            throw new RuntimeException('Google Ads OAuth state is invalid.');
        }

        if (($payload['provider_id'] ?? null) !== $provider->id
            || ($payload['organization_id'] ?? null) !== $provider->organization_id
            || ($payload['slug'] ?? null) !== self::SLUG) {
            throw new RuntimeException('Google Ads OAuth state does not match provider.');
        }

        if (($payload['exp'] ?? 0) < now()->timestamp) {
            throw new RuntimeException('Google Ads OAuth state has expired.');
        }
    }

    protected function redirectUri(): string
    {
        $configured = config('marketing.providers.google_ads.redirect_uri');

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
        $scopes = config('marketing.providers.google_ads.scopes', []);

        return array_values(array_filter(array_map(
            'strval',
            is_array($scopes) ? $scopes : [],
        )));
    }

    protected function assertConfigured(): void
    {
        if (! config('marketing.providers.google_ads.client_id')
            || ! config('marketing.providers.google_ads.client_secret')) {
            throw new RuntimeException(
                'Google Ads OAuth is not configured (GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET).'
            );
        }
    }

    /**
     * @return list<string>
     */
    protected function normalizeScopes(mixed $scopes): array
    {
        if (is_string($scopes)) {
            return array_values(array_filter(preg_split('/\s+/', trim($scopes)) ?: []));
        }

        if (is_array($scopes)) {
            return array_values(array_filter(array_map('strval', $scopes)));
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function healthFailure(string $status, string $message, bool $refreshCapable): array
    {
        return [
            'healthy' => false,
            'status' => $status,
            'message' => $message,
            'checked_at' => now()->toIso8601String(),
            'metadata' => [
                'refresh_capable' => $refreshCapable,
                'api_reachable' => false,
            ],
        ];
    }

    protected function looksLikeInvalidToken(string $message): bool
    {
        $message = strtolower($message);

        return str_contains($message, 'invalid_token')
            || str_contains($message, 'invalid credentials')
            || str_contains($message, 'invalid authentication credentials')
            || str_contains($message, 'invalid_grant')
            || str_contains($message, 'expired')
            || str_contains($message, 'revoked')
            || str_contains($message, 'unauthenticated');
    }

    protected function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
