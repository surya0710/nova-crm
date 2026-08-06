<?php

namespace App\Services\Marketing\Providers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin Google OAuth and Google Ads API client.
 *
 * This class performs provider communication only. It never persists
 * credentials, provider state, or synchronization data.
 */
class GoogleAdsClient
{
    public const SEARCH_MAX_PAGES = 10;

    /**
     * @param  list<string>  $scopes
     */
    public function authorizationUrl(string $redirectUri, string $state, array $scopes): string
    {
        $query = http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);

        return rtrim((string) config('marketing.providers.google_ads.authorization_url'), '/').'?'.$query;
    }

    /**
     * @return array<string, mixed>
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        return $this->tokenRequest([
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        return $this->tokenRequest([
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function tokenInfo(string $accessToken): array
    {
        $response = $this->http()->get(
            (string) config('marketing.providers.google_ads.token_info_url'),
            ['access_token' => $accessToken],
        );

        return $this->successfulPayload($response, 'Google OAuth token validation');
    }

    /**
     * Calls the least-privileged Google Ads endpoint used by health checks.
     *
     * @return array<string, mixed>
     */
    public function listAccessibleCustomers(string $accessToken): array
    {
        return $this->authenticatedRequest(
            'GET',
            'customers:listAccessibleCustomers',
            $accessToken,
            headers: ['developer-token' => $this->developerToken()],
        );
    }

    /**
     * Accessible customer resource names resolved to plain customer IDs.
     *
     * @return list<string>
     */
    public function listCustomers(string $accessToken): array
    {
        $payload = $this->listAccessibleCustomers($accessToken);
        $resourceNames = is_array($payload['resourceNames'] ?? null) ? $payload['resourceNames'] : [];

        $ids = [];
        foreach ($resourceNames as $resourceName) {
            if (! is_string($resourceName)) {
                continue;
            }

            $id = trim(str_replace('customers/', '', $resourceName));
            if ($id !== '' && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * Customer account metadata via Google Ads Query Language.
     *
     * @return array<string, mixed>
     */
    public function getCustomer(string $customerId, string $accessToken): array
    {
        $rows = $this->search(
            $customerId,
            $accessToken,
            'SELECT customer.id, customer.descriptive_name, customer.currency_code, '
            .'customer.time_zone, customer.manager FROM customer',
        );

        $customer = $rows[0]['customer'] ?? null;

        return is_array($customer) ? $customer : [];
    }

    /**
     * Conversion actions for a customer account (paginated).
     *
     * @return list<array<string, mixed>>
     */
    public function listConversionActions(string $customerId, string $accessToken): array
    {
        $rows = $this->search(
            $customerId,
            $accessToken,
            'SELECT conversion_action.id, conversion_action.name, conversion_action.category, '
            .'conversion_action.type, conversion_action.status, conversion_action.primary_for_goal '
            .'FROM conversion_action',
        );

        $actions = [];
        foreach ($rows as $row) {
            $action = $row['conversionAction'] ?? null;
            if (is_array($action)) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    /**
     * Upload normalized click/enhanced conversion payloads.
     *
     * @param  list<array<string, mixed>>  $conversions
     * @return array{
     *     ok: bool,
     *     uploaded: int,
     *     failed: int,
     *     results: list<array{ok: bool, message: string|null, response: array<string, mixed>|null}>,
     *     job_id: string|null
     * }
     */
    public function uploadOfflineConversions(
        string $customerId,
        string $accessToken,
        array $conversions,
    ): array {
        if ($conversions === []) {
            return [
                'ok' => true,
                'uploaded' => 0,
                'failed' => 0,
                'results' => [],
                'job_id' => null,
            ];
        }

        $payload = $this->authenticatedRequest(
            'POST',
            'customers/'.$customerId.':uploadClickConversions',
            $accessToken,
            json: [
                'conversions' => array_values($conversions),
                'partialFailure' => true,
                'validateOnly' => false,
            ],
            headers: ['developer-token' => $this->developerToken()],
        );

        return $this->normalizeUploadResponse($payload, count($conversions));
    }

    /**
     * Paginated GAQL search against a single customer account.
     *
     * @return list<array<string, mixed>>
     */
    public function search(string $customerId, string $accessToken, string $query): array
    {
        $rows = [];
        $pageToken = null;
        $pages = 0;

        do {
            $body = ['query' => $query];
            if ($pageToken !== null) {
                $body['pageToken'] = $pageToken;
            }

            $payload = $this->authenticatedRequest(
                'POST',
                'customers/'.$customerId.'/googleAds:search',
                $accessToken,
                json: $body,
                headers: ['developer-token' => $this->developerToken()],
            );

            foreach ($payload['results'] ?? [] as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }

            $pageToken = isset($payload['nextPageToken']) && is_string($payload['nextPageToken']) && $payload['nextPageToken'] !== ''
                ? $payload['nextPageToken']
                : null;
            $pages++;
        } while ($pageToken !== null && $pages < self::SEARCH_MAX_PAGES);

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     ok: bool,
     *     uploaded: int,
     *     failed: int,
     *     results: list<array{ok: bool, message: string|null, response: array<string, mixed>|null}>,
     *     job_id: string|null
     * }
     */
    protected function normalizeUploadResponse(array $payload, int $expected): array
    {
        $responses = is_array($payload['results'] ?? null) ? array_values($payload['results']) : [];
        $errorsByIndex = $this->partialFailureErrors($payload['partialFailureError'] ?? null);
        $fallbackError = $this->partialFailureMessage($payload['partialFailureError'] ?? null);
        $results = [];
        $uploaded = 0;
        $failed = 0;

        for ($index = 0; $index < $expected; $index++) {
            $messages = $errorsByIndex[$index] ?? [];

            if ($messages === [] && $fallbackError !== null && $errorsByIndex === []) {
                $messages[] = $fallbackError;
            }

            if ($messages !== []) {
                $failed++;
                $results[] = [
                    'ok' => false,
                    'message' => implode('; ', array_unique($messages)),
                    'response' => null,
                ];

                continue;
            }

            $response = $responses[$index] ?? null;
            if (! is_array($response)) {
                $failed++;
                $results[] = [
                    'ok' => false,
                    'message' => 'Google Ads upload response is missing a result for this conversion.',
                    'response' => null,
                ];

                continue;
            }

            $uploaded++;
            $results[] = [
                'ok' => true,
                'message' => null,
                'response' => $response,
            ];
        }

        return [
            'ok' => $failed === 0,
            'uploaded' => $uploaded,
            'failed' => $failed,
            'results' => $results,
            'job_id' => isset($payload['jobId']) && is_scalar($payload['jobId'])
                ? (string) $payload['jobId']
                : null,
        ];
    }

    /**
     * @return array<int, list<string>>
     */
    protected function partialFailureErrors(mixed $partialFailure): array
    {
        if (! is_array($partialFailure)) {
            return [];
        }

        $errors = [];
        foreach ($partialFailure['details'] ?? [] as $detail) {
            if (! is_array($detail)) {
                continue;
            }

            foreach ($detail['errors'] ?? [] as $error) {
                if (! is_array($error)) {
                    continue;
                }

                $index = $this->conversionErrorIndex($error);
                if ($index === null) {
                    continue;
                }

                $message = isset($error['message']) && is_scalar($error['message'])
                    ? trim((string) $error['message'])
                    : '';
                $errors[$index][] = $message !== ''
                    ? mb_substr($message, 0, 500)
                    : 'Google Ads rejected this conversion.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $error
     */
    protected function conversionErrorIndex(array $error): ?int
    {
        $elements = $error['location']['fieldPathElements'] ?? null;
        if (! is_array($elements)) {
            return null;
        }

        foreach ($elements as $element) {
            if (! is_array($element) || ($element['fieldName'] ?? null) !== 'conversions') {
                continue;
            }

            if (isset($element['index']) && is_numeric($element['index'])) {
                return (int) $element['index'];
            }
        }

        return null;
    }

    protected function partialFailureMessage(mixed $partialFailure): ?string
    {
        if (! is_array($partialFailure)) {
            return null;
        }

        $message = $partialFailure['message'] ?? null;
        if (! is_scalar($message) || trim((string) $message) === '') {
            return null;
        }

        return mb_substr(trim((string) $message), 0, 500);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $json
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    public function authenticatedRequest(
        string $method,
        string $path,
        string $accessToken,
        array $query = [],
        array $json = [],
        array $headers = [],
    ): array {
        $request = $this->http()
            ->withToken($accessToken)
            ->withHeaders($headers);

        $response = $request->send(strtoupper($method), $this->apiUrl($path), array_filter([
            'query' => $query,
            'json' => $json,
        ], static fn (array $value): bool => $value !== []));

        return $this->successfulPayload($response, 'Google Ads API request');
    }

    public function revokeToken(string $token): void
    {
        $response = $this->http()
            ->asForm()
            ->post((string) config('marketing.providers.google_ads.revoke_url'), [
                'token' => $token,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Google OAuth token revoke failed: '.$this->errorMessage($response)
            );
        }
    }

    /**
     * @param  array<string, scalar>  $payload
     * @return array<string, mixed>
     */
    protected function tokenRequest(array $payload): array
    {
        $response = $this->http()
            ->asForm()
            ->post((string) config('marketing.providers.google_ads.token_url'), $payload);

        return $this->successfulPayload($response, 'Google OAuth token exchange');
    }

    /**
     * @return array<string, mixed>
     */
    protected function successfulPayload(Response $response, string $operation): array
    {
        $payload = $response->json();

        if ($response->failed() || ! is_array($payload) || isset($payload['error'])) {
            throw new RuntimeException($operation.' failed: '.$this->errorMessage($response));
        }

        return $payload;
    }

    protected function http(): PendingRequest
    {
        return Http::timeout((int) config('marketing.providers.google_ads.timeout', 15))
            ->acceptJson();
    }

    protected function apiUrl(string $path): string
    {
        $base = rtrim((string) config('marketing.providers.google_ads.api_base_url'), '/');
        $version = trim((string) config('marketing.providers.google_ads.api_version'), '/');

        return $base.'/'.$version.'/'.ltrim($path, '/');
    }

    protected function clientId(): string
    {
        $clientId = trim((string) config('marketing.providers.google_ads.client_id'));

        if ($clientId === '') {
            throw new RuntimeException('GOOGLE_CLIENT_ID is not configured.');
        }

        return $clientId;
    }

    protected function clientSecret(): string
    {
        $clientSecret = trim((string) config('marketing.providers.google_ads.client_secret'));

        if ($clientSecret === '') {
            throw new RuntimeException('GOOGLE_CLIENT_SECRET is not configured.');
        }

        return $clientSecret;
    }

    protected function developerToken(): string
    {
        $developerToken = trim((string) config('marketing.providers.google_ads.developer_token'));

        if ($developerToken === '') {
            throw new RuntimeException('GOOGLE_ADS_DEVELOPER_TOKEN is not configured.');
        }

        return $developerToken;
    }

    protected function errorMessage(Response $response): string
    {
        $payload = $response->json();
        $message = null;

        if (is_array($payload)) {
            $error = $payload['error'] ?? null;

            if (is_array($error)) {
                $message = $error['message'] ?? $error['status'] ?? null;
            } elseif (is_string($error)) {
                $description = $payload['error_description'] ?? null;
                $message = is_string($description) && $description !== ''
                    ? $error.': '.$description
                    : $error;
            }

            $message ??= $payload['error_description'] ?? $payload['message'] ?? null;
        }

        $normalized = is_scalar($message) ? trim((string) $message) : '';

        return $normalized !== ''
            ? mb_substr($normalized, 0, 500)
            : 'unknown Google API error (HTTP '.$response->status().')';
    }
}
