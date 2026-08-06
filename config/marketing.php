<?php

use App\Services\Marketing\Providers\GoogleAdsProvider;
use App\Services\Marketing\Providers\MetaMarketingProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Visitor Tracking Runtime (P7B.2)
    |--------------------------------------------------------------------------
    |
    | Cookie names and lifetimes for anonymous visitor identification.
    | Cookies carry opaque platform-issued UUIDs only. No fingerprinting.
    |
    */

    'tracking' => [

        'visitor_cookie' => env('MARKETING_VISITOR_COOKIE', 'nova_mk_visitor'),

        'session_cookie' => env('MARKETING_SESSION_COOKIE', 'nova_mk_session'),

        // Long-term anonymous identity. Default: 2 years.
        'visitor_lifetime_minutes' => (int) env('MARKETING_VISITOR_LIFETIME_MINUTES', 60 * 24 * 730),

        // Inactivity window after which a session ends and a new one starts.
        'session_timeout_minutes' => (int) env('MARKETING_SESSION_TIMEOUT_MINUTES', 30),

        // Skip last-seen / last-activity writes when the stored timestamp is
        // newer than this, so rapid page views don't cause redundant updates.
        'activity_granularity_seconds' => (int) env('MARKETING_ACTIVITY_GRANULARITY_SECONDS', 60),

        // Per-IP throttle for the public tracking endpoint.
        'rate_limit_per_minute' => (int) env('MARKETING_TRACKING_RATE_LIMIT', 120),

    ],

    /*
    |--------------------------------------------------------------------------
    | Attribution (P7B.4)
    |--------------------------------------------------------------------------
    |
    | Only first_touch is implemented. The attribution_model column is a
    | string so last_touch / linear / position_based / time_decay can be
    | added later without schema changes.
    |
    */

    'attribution' => [
        'default_model' => env('MARKETING_ATTRIBUTION_MODEL', 'first_touch'),
        'supported_models' => ['first_touch'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversion Events (P7B.5)
    |--------------------------------------------------------------------------
    |
    | Canonical, provider-agnostic conversion event names. Append-only.
    | Providers consume these later; they never define them.
    |
    */

    'conversions' => [
        'supported_events' => [
            'lead_created',
            'lead_converted',
            'customer_created',
            'opportunity_created',
            'opportunity_won',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Historical Backfill (P7B.6)
    |--------------------------------------------------------------------------
    |
    | Maintenance-only. Deterministic visitor matching reads these custom_fields
    | keys on leads. No heuristic identity matching is performed.
    |
    */

    'backfill' => [
        'chunk_size' => (int) env('MARKETING_BACKFILL_CHUNK', 100),
        'visitor_uuid_field' => 'visitor_uuid',
        'session_uuid_field' => 'session_uuid',
        'cursor_ttl_seconds' => (int) env('MARKETING_BACKFILL_CURSOR_TTL', 60 * 60 * 24 * 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Integration Platform
    |--------------------------------------------------------------------------
    |
    | PLATFORM credentials (app ID / app secret) live in .env — they identify
    | NovaCRM's applications registered with Meta/Google/etc. There is one set
    | per environment (dev/staging/production). They are NOT customer tokens.
    |
    | TENANT credentials (access tokens, refresh tokens, account IDs, status)
    | live only in marketing_providers / marketing_provider_credentials and are
    | written exclusively through MarketingProviderService.
    |
    | Extension points (adapters declare capabilities):
    |   oauth, asset_discovery, lead_form_sync, lead_import, sync, webhooks, offline_conversions, audiences
    |
    */

    'providers' => [

        'statuses' => [
            'connected',
            'disconnected',
            'expired',
            'error',
        ],

        'synchronization' => [
            'types' => [
                'lead_import',
                'webhook_processing',
                'asset_discovery',
                'form_sync',
                'conversion_upload',
            ],
            'directions' => [
                'inbound',
                'outbound',
            ],
            'statuses' => [
                'pending',
                'running',
                'completed',
                'partial',
                'failed',
                'cancelled',
            ],
        ],

        // Per-IP throttle for public provider webhook endpoints.
        'webhook_rate_limit_per_minute' => (int) env('MARKETING_WEBHOOK_RATE_LIMIT', 120),

        // Planned providers (catalog only until a driver is registered).
        'catalog' => [
            'meta' => ['name' => 'Meta Business', 'channel' => 'paid_social'],
            'google_ads' => ['name' => 'Google Ads', 'channel' => 'paid_search'],
            'linkedin' => ['name' => 'LinkedIn Ads', 'channel' => 'paid_social'],
            'microsoft_ads' => ['name' => 'Microsoft Ads', 'channel' => 'paid_search'],
            'tiktok' => ['name' => 'TikTok Ads', 'channel' => 'paid_social'],
        ],

        // Registered adapter classes keyed by slug.
        'drivers' => [
            'meta' => MetaMarketingProvider::class,
            'google_ads' => GoogleAdsProvider::class,
        ],

        /*
        | Meta — PLATFORM application config only (NovaCRM's Meta app).
        | Tenant access tokens are NEVER stored here.
        */
        'meta' => [
            'client_id' => env('META_APP_ID'),
            'client_secret' => env('META_APP_SECRET'),
            'redirect_uri' => env('META_REDIRECT_URI'),
            'api_version' => env('META_GRAPH_API_VERSION', 'v21.0'),
            'timeout' => (int) env('META_HTTP_TIMEOUT', 15),
            'graph_base_url' => env('META_GRAPH_BASE_URL', 'https://graph.facebook.com'),
            'oauth_dialog_url' => env('META_OAUTH_DIALOG_URL', 'https://www.facebook.com'),
            // Application-level webhook subscription verify token (not a tenant secret).
            'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
            'scopes' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env(
                    'META_OAUTH_SCOPES',
                    'business_management,ads_read,pages_show_list,pages_read_engagement,leads_retrieval'
                ))
            ))),
        ],

        /*
        | Google Ads — PLATFORM application config only (NovaCRM's Google app).
        | Tenant access/refresh tokens and customer IDs are NEVER stored here.
        */
        'google_ads' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
            'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
            'api_version' => env('GOOGLE_ADS_API_VERSION', 'v22'),
            'timeout' => (int) env('GOOGLE_HTTP_TIMEOUT', 15),
            'authorization_url' => env(
                'GOOGLE_AUTHORIZATION_URL',
                'https://accounts.google.com/o/oauth2/v2/auth'
            ),
            'token_url' => env('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
            'token_info_url' => env('GOOGLE_TOKEN_INFO_URL', 'https://oauth2.googleapis.com/tokeninfo'),
            'revoke_url' => env('GOOGLE_REVOKE_URL', 'https://oauth2.googleapis.com/revoke'),
            'api_base_url' => env('GOOGLE_ADS_API_BASE_URL', 'https://googleads.googleapis.com'),
            'scopes' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env(
                    'GOOGLE_OAUTH_SCOPES',
                    'https://www.googleapis.com/auth/adwords,openid,email'
                ))
            ))),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Channel Classification (P7B.3)
    |--------------------------------------------------------------------------
    |
    | Deterministic, provider-agnostic classification rules consumed by
    | MarketingChannelClassificationService. Precedence follows the Marketing
    | Attribution Contract: click ID → UTM → referrer rules → direct.
    |
    | Domain patterns: entries containing a dot match the host exactly or as
    | a suffix (e.g. "t.co" matches "t.co" and "sub.t.co"); entries without
    | a dot match any host label (e.g. "google" matches "www.google.co.uk").
    | Map keys are the canonical source names stored on touches.
    |
    */

    'classification' => [

        'click_ids' => [
            'gclid' => ['channel' => 'paid_search', 'source' => 'google', 'medium' => 'cpc'],
            'msclkid' => ['channel' => 'paid_search', 'source' => 'bing', 'medium' => 'cpc'],
            'fbclid' => ['channel' => 'paid_social', 'source' => 'facebook', 'medium' => 'paid_social'],
        ],

        'search_engines' => [
            'google' => ['google'],
            'bing' => ['bing'],
            'yahoo' => ['yahoo'],
            'duckduckgo' => ['duckduckgo'],
            'baidu' => ['baidu'],
            'yandex' => ['yandex'],
            'ecosia' => ['ecosia'],
        ],

        'social_networks' => [
            'facebook' => ['facebook', 'fb', 'messenger'],
            'instagram' => ['instagram'],
            'linkedin' => ['linkedin', 'lnkd'],
            'x' => ['twitter', 'x.com', 't.co'],
            'threads' => ['threads'],
            'reddit' => ['reddit', 'redd.it'],
            'youtube' => ['youtube', 'youtu.be'],
            'tiktok' => ['tiktok'],
            'pinterest' => ['pinterest'],
            'whatsapp' => ['whatsapp', 'wa.me'],
        ],

        'paid_mediums' => [
            'cpc', 'ppc', 'sem', 'paid', 'paidsearch', 'paid-search', 'paid_search',
            'paidsocial', 'paid-social', 'paid_social', 'retargeting',
        ],

        'social_mediums' => [
            'social', 'social-network', 'social_media', 'sm', 'organic_social', 'organic-social',
        ],

        'email_mediums' => [
            'email', 'e-mail', 'e_mail', 'newsletter',
        ],

        'display_mediums' => [
            'display', 'banner', 'cpm', 'programmatic',
        ],

    ],

];
