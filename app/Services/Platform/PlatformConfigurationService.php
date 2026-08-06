<?php

namespace App\Services\Platform;

use App\Models\PlatformSetting;
use App\Models\PlatformUser;
use Illuminate\Support\Facades\Cache;

class PlatformConfigurationService
{
    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $record = PlatformSetting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        if (! $record) {
            return $default;
        }

        return $record->value ?? $default;
    }

    public function set(string $group, string $key, mixed $value, ?PlatformUser $actor = null): PlatformSetting
    {
        $record = PlatformSetting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value]
        );

        Cache::forget("platform.settings.{$group}.{$key}");

        if ($actor) {
            app(PlatformAuditService::class)->log('configuration.updated', $actor, null, [
                'group' => $group,
                'key' => $key,
            ]);
        }

        return $record;
    }

    public function group(string $group): array
    {
        $defaults = config("platform.configuration_defaults.{$group}", []);
        $stored = PlatformSetting::query()
            ->where('group', $group)
            ->get()
            ->mapWithKeys(fn (PlatformSetting $s) => [$s->key => $s->value])
            ->all();

        if ($group === 'branding' || $group === 'domains' || $group === 'regional' || $group === 'ai' || $group === 'organization_defaults') {
            $override = $this->get('configuration', $group, []);

            return array_replace_recursive($defaults, is_array($override) ? $override : [], $stored);
        }

        return array_replace_recursive($defaults, $stored);
    }

    public function all(): array
    {
        return [
            'branding' => $this->group('branding'),
            'domains' => $this->group('domains'),
            'regional' => $this->group('regional'),
            'ai' => $this->group('ai'),
            'organization_defaults' => $this->group('organization_defaults'),
            'email_templates' => $this->get('configuration', 'email_templates', [
                'welcome' => ['subject' => 'Welcome to '.config('branding.product_name', 'Konnect Nex'), 'body' => 'Your organization is ready.'],
                'trial_ending' => ['subject' => 'Your trial is ending', 'body' => 'Upgrade to keep access.'],
            ]),
        ];
    }

    public function updateGroup(string $group, array $data, PlatformUser $actor): array
    {
        $this->set('configuration', $group, $data, $actor);

        return $this->group($group);
    }
}
