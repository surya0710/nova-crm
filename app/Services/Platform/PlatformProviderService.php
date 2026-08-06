<?php

namespace App\Services\Platform;

use Illuminate\Support\Facades\Cache;

class PlatformProviderService
{
    public function catalog(): array
    {
        return collect(config('platform.providers', []))
            ->map(fn (array $provider, string $key) => $this->inspect($key, $provider))
            ->values()
            ->all();
    }

    public function healthSummary(): array
    {
        $catalog = $this->catalog();
        $healthy = collect($catalog)->where('status', 'configured')->count();
        $total = count($catalog);

        return [
            'healthy' => $healthy,
            'total' => $total,
            'status' => $healthy === $total ? 'healthy' : ($healthy === 0 ? 'critical' : 'partial'),
            'items' => $catalog,
        ];
    }

    public function inspect(string $key, ?array $provider = null): array
    {
        $provider ??= config("platform.providers.{$key}");

        if (! $provider) {
            return [
                'key' => $key,
                'label' => $key,
                'status' => 'unknown',
                'configured_keys' => 0,
                'missing_keys' => [],
                'category' => 'other',
            ];
        }

        $envKeys = $provider['env_keys'] ?? [];
        $configured = [];
        $missing = [];

        foreach ($envKeys as $envKey) {
            $value = env($envKey);
            if ($value !== null && $value !== '') {
                $configured[] = $envKey;
            } else {
                $missing[] = $envKey;
            }
        }

        $status = count($missing) === 0 ? 'configured' : (count($configured) > 0 ? 'partial' : 'missing');

        return [
            'key' => $key,
            'label' => $provider['label'] ?? $key,
            'category' => $provider['category'] ?? 'other',
            'status' => $status,
            'configured_keys' => count($configured),
            'total_keys' => count($envKeys),
            'missing_keys' => $missing,
            'env_keys' => $envKeys,
        ];
    }

    public function validate(string $key): array
    {
        $result = $this->inspect($key);
        $result['valid'] = $result['status'] === 'configured';
        $result['message'] = $result['valid']
            ? __('Credentials appear configured.')
            : __('Missing environment keys: :keys', ['keys' => implode(', ', $result['missing_keys'])]);

        return $result;
    }

    public function test(string $key): array
    {
        $validation = $this->validate($key);

        if (! $validation['valid']) {
            return [
                'ok' => false,
                'message' => $validation['message'],
                'provider' => $validation,
            ];
        }

        // Credential presence test only — no outbound network calls.
        Cache::put("platform.provider.test.{$key}", now()->toIso8601String(), 3600);

        return [
            'ok' => true,
            'message' => __('Credential check passed. Live connectivity tests are provider-specific.'),
            'provider' => $validation,
            'tested_at' => now()->toIso8601String(),
        ];
    }
}
