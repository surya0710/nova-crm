<?php

namespace App\Services\Platform;

use App\Models\IndustryTemplateVersion;
use App\Models\Organization;
use App\Models\OrganizationTemplateApplication;
use App\Models\PlatformUser;

class IndustryTemplateApplicationService
{
    public function applyToNewOrganization(
        Organization $organization,
        IndustryTemplateVersion $version,
        PlatformUser $actor,
        array $explicitOrganizationValues = [],
    ): OrganizationTemplateApplication {
        $payload = $version->payload;
        $settings = $organization->settings ?? [];
        $summary = [];
        $appliedSections = [];
        $skippedSections = [];

        $this->applySettings($organization, $payload['settings'] ?? [], $explicitOrganizationValues);
        $this->mergeSettingsSection($settings, 'terminology', $payload['terminology'] ?? [], $appliedSections, $summary);
        $this->mergeSettingsSection($settings, 'business_calendar', $payload['business_calendar'] ?? [], $appliedSections, $summary);
        $this->mergeSettingsSection($settings, 'industry_lead_lifecycle', $payload['lead_lifecycle'] ?? [], $appliedSections, $summary);
        $this->mergeSettingsSection($settings, 'industry_customer_configuration', $payload['customer_configuration'] ?? [], $appliedSections, $summary);
        $this->mergeSettingsSection($settings, 'industry_pipeline_blueprints', $payload['pipelines'] ?? [], $appliedSections, $summary);
        $this->mergeSettingsSection($settings, 'dashboard_layout', $payload['dashboard'] ?? [], $appliedSections, $summary);
        $this->mergeSettingsSection($settings, 'report_presets', $payload['reports'] ?? [], $appliedSections, $summary);
        $this->mergeSettingsSection($settings, 'notification_preferences', $payload['notification_preferences'] ?? [], $appliedSections, $summary);
        $this->mergeSettingsSection($settings, 'task_blueprints', $payload['task_blueprints'] ?? [], $appliedSections, $summary);
        $this->mergeSettingsSection($settings, 'field_blueprints', $payload['field_blueprints'] ?? [], $appliedSections, $summary);

        foreach (['automation_blueprints', 'email_template_blueprints'] as $futureSection) {
            if (! empty($payload[$futureSection])) {
                $skippedSections[$futureSection] = 'Deferred future capability; preserved in template version only.';
            }
        }

        $organization->forceFill([
            'settings' => $settings,
        ])->save();

        return OrganizationTemplateApplication::create([
            'organization_id' => $organization->id,
            'industry_template_id' => $version->industry_template_id,
            'industry_template_version_id' => $version->id,
            'applied_by_platform_user_id' => $actor->id,
            'application_type' => 'initial_onboarding',
            'status' => 'applied',
            'payload_hash' => $version->payload_hash,
            'applied_sections' => array_values(array_unique($appliedSections)),
            'skipped_sections' => $skippedSections,
            'summary' => $summary,
            'applied_at' => now(),
        ]);
    }

    protected function applySettings(Organization $organization, array $templateSettings, array $explicitValues): void
    {
        $attributes = [];

        foreach (['timezone', 'currency', 'tax_name'] as $field) {
            if (
                array_key_exists($field, $templateSettings)
                && empty($explicitValues[$field])
            ) {
                $attributes[$field] = $templateSettings[$field];
            }
        }

        if ($attributes !== []) {
            $organization->forceFill($attributes)->save();
        }
    }

    protected function mergeSettingsSection(
        array &$settings,
        string $key,
        array $value,
        array &$appliedSections,
        array &$summary,
    ): void {
        if ($value === []) {
            return;
        }

        $settings[$key] = $value;
        $appliedSections[] = $key;
        $summary[$key] = $this->countSection($value);
    }

    protected function countSection(array $value): int
    {
        if (array_is_list($value)) {
            return count($value);
        }

        return count(array_filter($value, fn ($item) => $item !== null && $item !== []));
    }
}
