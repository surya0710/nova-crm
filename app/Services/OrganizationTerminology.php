<?php

namespace App\Services;

use App\Models\Organization;

class OrganizationTerminology
{
    public function __construct(protected TenantContext $tenant) {}

    /**
     * @return array<string, string>
     */
    public function all(?Organization $organization = null): array
    {
        $organization ??= $this->tenant->get();
        $defaults = config('terminology.defaults', []);
        $industryType = $organization?->settings['industry_type'] ?? 'general';
        $preset = config("terminology.industries.{$industryType}.terms", []);
        $overrides = $organization?->settings['terminology'] ?? [];

        return array_merge(
            $defaults,
            is_array($preset) ? $preset : [],
            array_filter(is_array($overrides) ? $overrides : [], fn ($value) => filled($value)),
        );
    }

    public function get(string $key, ?Organization $organization = null): string
    {
        $terms = $this->all($organization);

        return $terms[$key] ?? config("terminology.defaults.{$key}", ucfirst($key));
    }

    public function industryType(?Organization $organization = null): string
    {
        $organization ??= $this->tenant->get();

        return $organization?->settings['industry_type'] ?? 'general';
    }

    /**
     * @return array<string, string>
     */
    public function presetForIndustry(string $industryType): array
    {
        $defaults = config('terminology.defaults', []);
        $preset = config("terminology.industries.{$industryType}.terms", []);

        return array_merge($defaults, is_array($preset) ? $preset : []);
    }
}
