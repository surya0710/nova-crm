<?php

namespace App\Services\Marketing\Providers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin Meta Graph HTTP client (P7C.2 + P7C.3 discovery).
 *
 * No Eloquent writes. Used only by MetaMarketingProvider.
 * Discovery methods are read-only — no webhooks, subscriptions, or sync writes.
 */
class MetaGraphClient
{
    public const DISCOVERY_PAGE_LIMIT = 100;

    public const DISCOVERY_MAX_PAGES = 10;

    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        return $this->get('oauth/access_token', [
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);
    }

    public function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        return $this->get('oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'fb_exchange_token' => $shortLivedToken,
        ]);
    }

    /**
     * @param  list<string>  $fields
     */
    public function getMe(string $accessToken, array $fields = ['id', 'name']): array
    {
        return $this->get('me', [
            'fields' => implode(',', $fields),
            'access_token' => $accessToken,
        ]);
    }

    public function revokePermissions(string $accessToken): void
    {
        $response = $this->http()
            ->delete($this->graphUrl('me/permissions'), [
                'access_token' => $accessToken,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Meta permission revoke failed: '.$this->errorMessage($response->json() ?? [])
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listBusinesses(string $accessToken): array
    {
        return $this->getPaginated('me/businesses', [
            'fields' => 'id,name',
            'access_token' => $accessToken,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listOwnedAdAccounts(string $businessId, string $accessToken): array
    {
        return $this->getPaginated($businessId.'/owned_ad_accounts', [
            'fields' => 'id,account_id,name,currency,account_status',
            'access_token' => $accessToken,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listClientAdAccounts(string $businessId, string $accessToken): array
    {
        return $this->getPaginated($businessId.'/client_ad_accounts', [
            'fields' => 'id,account_id,name,currency,account_status',
            'access_token' => $accessToken,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listOwnedPages(string $businessId, string $accessToken): array
    {
        return $this->getPaginated($businessId.'/owned_pages', [
            'fields' => 'id,name',
            'access_token' => $accessToken,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listClientPages(string $businessId, string $accessToken): array
    {
        return $this->getPaginated($businessId.'/client_pages', [
            'fields' => 'id,name',
            'access_token' => $accessToken,
        ]);
    }

    /**
     * User-scoped pages (fallback when business pages are empty / inaccessible).
     *
     * @return list<array<string, mixed>>
     */
    public function listUserPages(string $accessToken): array
    {
        return $this->getPaginated('me/accounts', [
            'fields' => 'id,name',
            'access_token' => $accessToken,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listOwnedPixels(string $businessId, string $accessToken): array
    {
        return $this->getPaginated($businessId.'/owned_pixels', [
            'fields' => 'id,name',
            'access_token' => $accessToken,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAdAccountPixels(string $adAccountId, string $accessToken): array
    {
        $actId = str_starts_with($adAccountId, 'act_') ? $adAccountId : 'act_'.$adAccountId;

        return $this->getPaginated($actId.'/adspixels', [
            'fields' => 'id,name',
            'access_token' => $accessToken,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listLeadForms(string $pageId, string $accessToken): array
    {
        return $this->getPaginated($pageId.'/leadgen_forms', [
            'fields' => 'id,name,status,locale',
            'access_token' => $accessToken,
        ]);
    }

    /**
     * Single lead form metadata including questions (P7C.4).
     *
     * @return array<string, mixed>
     */
    public function getLeadForm(string $formId, string $accessToken): array
    {
        return $this->get($formId, [
            'fields' => 'id,name,status,locale,questions,updated_time',
            'access_token' => $accessToken,
        ]);
    }

    /**
     * Lead submissions for a form (P7C.5). Metadata + field_data only.
     *
     * @return list<array<string, mixed>>
     */
    public function listFormLeads(string $formId, string $accessToken): array
    {
        return $this->getPaginated($formId.'/leads', [
            'fields' => 'id,created_time,ad_id,ad_name,form_id,field_data',
            'access_token' => $accessToken,
        ]);
    }

    /**
     * Single lead submission by leadgen id (P7C.7 webhook processing).
     * Same field shape as listFormLeads so normalization is identical.
     *
     * @return array<string, mixed>
     */
    public function getLead(string $leadId, string $accessToken): array
    {
        return $this->get($leadId, [
            'fields' => 'id,created_time,ad_id,ad_name,form_id,field_data',
            'access_token' => $accessToken,
        ]);
    }

    /**
     * Submit Conversions API events to a Pixel (P7C.9).
     *
     * @param  list<array<string, mixed>>  $events
     * @return array<string, mixed>
     */
    public function sendPixelEvents(
        string $pixelId,
        string $accessToken,
        array $events,
        ?string $testEventCode = null,
    ): array {
        $payload = [
            'data' => array_values($events),
            'access_token' => $accessToken,
        ];

        if ($testEventCode !== null && $testEventCode !== '') {
            $payload['test_event_code'] = $testEventCode;
        }

        return $this->post($pixelId.'/events', $payload);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function post(string $path, array $body): array
    {
        $response = $this->http()->asJson()->post($this->graphUrl($path), $body);

        $payload = $response->json() ?? [];

        if ($response->failed() || isset($payload['error'])) {
            throw new RuntimeException(
                'Meta Graph request failed: '.$this->errorMessage(is_array($payload) ? $payload : [])
            );
        }

        return is_array($payload) ? $payload : [];
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return list<array<string, mixed>>
     */
    protected function getPaginated(string $path, array $query): array
    {
        $items = [];
        $query['limit'] = self::DISCOVERY_PAGE_LIMIT;
        $nextUrl = null;
        $pages = 0;

        do {
            if ($nextUrl !== null) {
                $response = $this->http()->get($nextUrl);
                $payload = $response->json() ?? [];
            } else {
                $response = $this->http()->get($this->graphUrl($path), $query);
                $payload = $response->json() ?? [];
            }

            if ($response->failed() || isset($payload['error'])) {
                throw new RuntimeException(
                    'Meta Graph request failed: '.$this->errorMessage(is_array($payload) ? $payload : [])
                );
            }

            foreach ($payload['data'] ?? [] as $row) {
                if (is_array($row)) {
                    $items[] = $row;
                }
            }

            $nextUrl = isset($payload['paging']['next']) && is_string($payload['paging']['next'])
                ? $payload['paging']['next']
                : null;
            $pages++;
        } while ($nextUrl !== null && $pages < self::DISCOVERY_MAX_PAGES);

        return $items;
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    protected function get(string $path, array $query): array
    {
        $response = $this->http()->get($this->graphUrl($path), $query);

        $payload = $response->json() ?? [];

        if ($response->failed() || isset($payload['error'])) {
            throw new RuntimeException(
                'Meta Graph request failed: '.$this->errorMessage($payload)
            );
        }

        return is_array($payload) ? $payload : [];
    }

    protected function http(): PendingRequest
    {
        return Http::timeout((int) config('marketing.providers.meta.timeout', 15))
            ->acceptJson();
    }

    protected function graphUrl(string $path): string
    {
        $base = rtrim((string) config('marketing.providers.meta.graph_base_url'), '/');
        $version = trim((string) config('marketing.providers.meta.api_version'), '/');

        return $base.'/'.$version.'/'.ltrim($path, '/');
    }

    protected function clientId(): string
    {
        $id = (string) config('marketing.providers.meta.client_id');

        if ($id === '') {
            throw new RuntimeException('META_APP_ID is not configured.');
        }

        return $id;
    }

    protected function clientSecret(): string
    {
        $secret = (string) config('marketing.providers.meta.client_secret');

        if ($secret === '') {
            throw new RuntimeException('META_APP_SECRET is not configured.');
        }

        return $secret;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function errorMessage(array $payload): string
    {
        $error = $payload['error'] ?? null;

        if (is_array($error)) {
            return (string) ($error['message'] ?? json_encode($error));
        }

        return 'unknown Meta API error';
    }
}
