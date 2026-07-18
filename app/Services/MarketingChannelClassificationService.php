<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Deterministic, provider-agnostic traffic classification (P7B.3).
 *
 * Pure computation only: this service never persists anything.
 * MarketingTrackingService consumes the classification result and remains
 * the single write authority.
 *
 * Precedence follows the Marketing Attribution Contract:
 * click ID → UTM parameters → referrer rules → direct.
 */
class MarketingChannelClassificationService
{
    public const DIRECT = 'direct';

    public const ORGANIC_SEARCH = 'organic_search';

    public const PAID_SEARCH = 'paid_search';

    public const ORGANIC_SOCIAL = 'organic_social';

    public const PAID_SOCIAL = 'paid_social';

    public const REFERRAL = 'referral';

    public const EMAIL = 'email';

    public const DISPLAY = 'display';

    public const OTHER = 'other';

    /**
     * Classify a page view from its URL and referrer.
     *
     * @return array{channel: string, source: ?string, medium: ?string, campaign: ?string, term: ?string, content: ?string, gclid: ?string, fbclid: ?string, msclkid: ?string, referrer_host: ?string}
     */
    public function classify(?string $url, ?string $referrer): array
    {
        $params = $this->extractTrackingParameters($url);
        $referrerHost = $this->normalizeHost($referrer);

        // A referrer from the tracked site itself is internal navigation,
        // not an acquisition signal.
        if ($referrerHost !== null && $referrerHost === $this->normalizeHost($url)) {
            $referrerHost = null;
        }

        [$channel, $source, $medium] = $this->resolveChannel($params, $referrerHost);

        return [
            'channel' => $channel,
            'source' => $source,
            'medium' => $medium,
            'campaign' => $params['utm_campaign'],
            'term' => $params['utm_term'],
            'content' => $params['utm_content'],
            'gclid' => $params['gclid'],
            'fbclid' => $params['fbclid'],
            'msclkid' => $params['msclkid'],
            'referrer_host' => $referrerHost,
        ];
    }

    /**
     * Extract recognized tracking parameters from a URL's query string.
     * Absent or empty values are null, never empty strings. utm_source and
     * utm_medium are lowercased; other values are stored as received after
     * trimming, per the contract.
     *
     * @return array{utm_source: ?string, utm_medium: ?string, utm_campaign: ?string, utm_term: ?string, utm_content: ?string, gclid: ?string, fbclid: ?string, msclkid: ?string}
     */
    public function extractTrackingParameters(?string $url): array
    {
        $result = [
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'utm_term' => null,
            'utm_content' => null,
            'gclid' => null,
            'fbclid' => null,
            'msclkid' => null,
        ];

        $query = $url ? parse_url(trim($url), PHP_URL_QUERY) : null;

        if (! $query) {
            return $result;
        }

        parse_str($query, $params);

        foreach ($params as $key => $value) {
            $key = strtolower((string) $key);

            if (! array_key_exists($key, $result) || ! is_string($value)) {
                continue;
            }

            $value = trim($value);

            if ($value === '') {
                continue;
            }

            if (in_array($key, ['utm_source', 'utm_medium'], true)) {
                $value = Str::lower($value);
            }

            $result[$key] = Str::limit($value, 255, '');
        }

        return $result;
    }

    /**
     * Remove recognized tracking parameters (utm_* and click IDs) from a URL,
     * per the contract's landing-page rule. Non-tracking parameters survive.
     */
    public function stripTrackingParameters(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parts = parse_url(trim($url));

        if ($parts === false || ! isset($parts['host'])) {
            return $url;
        }

        $query = null;

        if (isset($parts['query'])) {
            parse_str($parts['query'], $params);

            $clean = array_filter(
                $params,
                fn ($value, $key) => ! $this->isTrackingParameter((string) $key),
                ARRAY_FILTER_USE_BOTH,
            );

            $query = $clean === [] ? null : http_build_query($clean);
        }

        $rebuilt = (isset($parts['scheme']) ? $parts['scheme'].'://' : '//').$parts['host'];

        if (isset($parts['port'])) {
            $rebuilt .= ':'.$parts['port'];
        }

        $rebuilt .= $parts['path'] ?? '/';

        if ($query !== null) {
            $rebuilt .= '?'.$query;
        }

        if (isset($parts['fragment'])) {
            $rebuilt .= '#'.$parts['fragment'];
        }

        return $rebuilt;
    }

    /**
     * Lowercased host without a leading "www.", or null when unparseable.
     */
    public function normalizeHost(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $host = parse_url(trim($url), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower($host);

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }

    /**
     * @param  array{utm_source: ?string, utm_medium: ?string, gclid: ?string, fbclid: ?string, msclkid: ?string}  $params
     * @return array{0: string, 1: ?string, 2: ?string} [channel, source, medium]
     */
    protected function resolveChannel(array $params, ?string $referrerHost): array
    {
        $source = $params['utm_source'];
        $medium = $params['utm_medium'];

        // 1. Click identifiers are the strongest signal.
        foreach (config('marketing.classification.click_ids', []) as $param => $rule) {
            if ($params[$param] !== null) {
                return [$rule['channel'], $source ?? $rule['source'], $medium ?? $rule['medium']];
            }
        }

        // 2. Explicit UTM parameters.
        if ($source !== null || $medium !== null) {
            return $this->classifyUtm($source, $medium, $referrerHost);
        }

        // 3. Referrer rules.
        if ($referrerHost !== null) {
            if ($engine = $this->matchHost($referrerHost, 'search_engines')) {
                return [self::ORGANIC_SEARCH, $engine, 'organic'];
            }

            if ($network = $this->matchHost($referrerHost, 'social_networks')) {
                return [self::ORGANIC_SOCIAL, $network, 'social'];
            }

            return [self::REFERRAL, $referrerHost, 'referral'];
        }

        // 4. No signals at all.
        return [self::DIRECT, null, null];
    }

    /**
     * @return array{0: string, 1: ?string, 2: ?string}
     */
    protected function classifyUtm(?string $source, ?string $medium, ?string $referrerHost): array
    {
        $sourceType = $this->knownSourceType($source, $referrerHost);

        if ($this->mediumIn($medium, 'email_mediums')) {
            return [self::EMAIL, $source, $medium];
        }

        if ($this->mediumIn($medium, 'display_mediums')) {
            return [self::DISPLAY, $source, $medium];
        }

        if ($this->mediumIn($medium, 'paid_mediums')) {
            $paidSocial = $sourceType === 'social' || str_contains((string) $medium, 'social');

            return [$paidSocial ? self::PAID_SOCIAL : self::PAID_SEARCH, $source, $medium];
        }

        if ($sourceType === 'social' || $this->mediumIn($medium, 'social_mediums')) {
            return [self::ORGANIC_SOCIAL, $source, $medium];
        }

        if ($medium === 'organic' || $sourceType === 'search') {
            return [self::ORGANIC_SEARCH, $source, $medium];
        }

        if ($medium === 'referral') {
            return [self::REFERRAL, $source ?? $referrerHost, $medium];
        }

        return [self::OTHER, $source, $medium];
    }

    /**
     * Whether a UTM source (or, failing that, the referrer host) belongs to
     * a known search engine or social network.
     */
    protected function knownSourceType(?string $utmSource, ?string $referrerHost): ?string
    {
        foreach (array_filter([$utmSource, $referrerHost]) as $candidate) {
            if ($this->matchHost($candidate, 'search_engines')) {
                return 'search';
            }

            if ($this->matchHost($candidate, 'social_networks')) {
                return 'social';
            }
        }

        return null;
    }

    /**
     * Match a host (or bare source name) against a configured domain map,
     * returning the canonical source name.
     */
    protected function matchHost(string $host, string $map): ?string
    {
        $labels = explode('.', $host);

        foreach (config('marketing.classification.'.$map, []) as $name => $patterns) {
            foreach ([$name, ...$patterns] as $pattern) {
                if (str_contains($pattern, '.')) {
                    if ($host === $pattern || str_ends_with($host, '.'.$pattern)) {
                        return $name;
                    }
                } elseif (in_array($pattern, $labels, true)) {
                    return $name;
                }
            }
        }

        return null;
    }

    protected function mediumIn(?string $medium, string $configKey): bool
    {
        return $medium !== null
            && in_array($medium, config('marketing.classification.'.$configKey, []), true);
    }

    protected function isTrackingParameter(string $key): bool
    {
        $key = strtolower($key);

        return str_starts_with($key, 'utm_')
            || array_key_exists($key, config('marketing.classification.click_ids', []));
    }
}
