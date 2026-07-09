<?php

namespace App\Services\Platform;

use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class IndustryTemplatePayloadValidator
{
    protected array $topLevelKeys = [
        'schema_version',
        'metadata',
        'settings',
        'terminology',
        'business_calendar',
        'lead_lifecycle',
        'customer_configuration',
        'pipelines',
        'dashboard',
        'reports',
        'notification_preferences',
        'task_blueprints',
        'field_blueprints',
        'automation_blueprints',
        'email_template_blueprints',
    ];

    public function validate(array $payload): array
    {
        $payload = $this->withDefaults($payload);
        $errors = [];

        if ((int) ($payload['schema_version'] ?? 0) !== (int) config('industry_templates.schema_version')) {
            $errors['schema_version'] = __('Unsupported template schema version.');
        }

        $unknownKeys = array_diff(array_keys($payload), $this->topLevelKeys);
        if ($unknownKeys !== []) {
            $errors['payload'] = __('Unknown template sections: :sections', [
                'sections' => implode(', ', $unknownKeys),
            ]);
        }

        $this->validatePipelines($payload['pipelines'], $errors);
        $this->validateDashboard($payload['dashboard'], $errors);
        $this->validateReports($payload['reports'], $errors);
        $this->validateFieldBlueprints($payload['field_blueprints'], $errors);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $this->canonicalize($payload);
    }

    public function defaultPayload(): array
    {
        return [
            'schema_version' => (int) config('industry_templates.schema_version'),
            'metadata' => [],
            'settings' => [],
            'terminology' => [],
            'business_calendar' => [],
            'lead_lifecycle' => [],
            'customer_configuration' => [],
            'pipelines' => [],
            'dashboard' => [],
            'reports' => [],
            'notification_preferences' => [],
            'task_blueprints' => [],
            'field_blueprints' => [],
            'automation_blueprints' => [],
            'email_template_blueprints' => [],
        ];
    }

    public function canonicalize(array $payload): array
    {
        $payload = $this->withDefaults($payload);
        $ordered = [];

        foreach ($this->topLevelKeys as $key) {
            $ordered[$key] = $payload[$key];
        }

        return $ordered;
    }

    public function hash(array $payload): string
    {
        return hash('sha256', json_encode(
            $this->canonicalize($payload),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));
    }

    protected function withDefaults(array $payload): array
    {
        return array_replace($this->defaultPayload(), $payload);
    }

    protected function validatePipelines(array $pipelines, array &$errors): void
    {
        if ($pipelines === []) {
            return;
        }

        $defaultCount = collect($pipelines)->where('is_default', true)->count();
        if ($defaultCount !== 1) {
            $errors['pipelines'] = __('Templates with pipelines must define exactly one default pipeline.');
        }

        foreach ($pipelines as $pipelineIndex => $pipeline) {
            $stageOrders = [];
            $stageKeys = [];

            foreach (($pipeline['stages'] ?? []) as $stageIndex => $stage) {
                $key = $stage['key'] ?? null;
                $order = $stage['order'] ?? null;
                $type = $stage['type'] ?? null;

                if (! $key) {
                    $errors["pipelines.{$pipelineIndex}.stages.{$stageIndex}.key"] = __('Pipeline stages require stable keys.');
                } elseif (in_array($key, $stageKeys, true)) {
                    $errors["pipelines.{$pipelineIndex}.stages.{$stageIndex}.key"] = __('Pipeline stage keys must be unique within a pipeline.');
                }

                if ($order === null || in_array($order, $stageOrders, true)) {
                    $errors["pipelines.{$pipelineIndex}.stages.{$stageIndex}.order"] = __('Pipeline stage order must be present and unique.');
                }

                if (($stage['is_terminal'] ?? false) && ! in_array($type, ['won', 'lost'], true)) {
                    $errors["pipelines.{$pipelineIndex}.stages.{$stageIndex}.type"] = __('Terminal pipeline stages must be won or lost.');
                }

                $stageKeys[] = $key;
                $stageOrders[] = $order;
            }
        }
    }

    protected function validateDashboard(array $dashboard, array &$errors): void
    {
        $allowedWidgets = config('industry_templates.dashboard_widgets', []);

        foreach (($dashboard['layout'] ?? []) as $index => $widget) {
            if (! in_array($widget['widget_key'] ?? null, $allowedWidgets, true)) {
                $errors["dashboard.layout.{$index}.widget_key"] = __('Dashboard widget is not supported.');
            }
        }
    }

    protected function validateReports(array $reports, array &$errors): void
    {
        $allowedReports = config('industry_templates.report_types', []);
        $keys = [];

        foreach ($reports as $index => $report) {
            $key = $report['key'] ?? null;

            if (! $key) {
                $errors["reports.{$index}.key"] = __('Report presets require stable keys.');
            } elseif (in_array($key, $keys, true)) {
                $errors["reports.{$index}.key"] = __('Report preset keys must be unique.');
            }

            if (! in_array($report['report_type'] ?? null, $allowedReports, true)) {
                $errors["reports.{$index}.report_type"] = __('Report preset type is not supported.');
            }

            $keys[] = $key;
        }
    }

    protected function validateFieldBlueprints(array $fields, array &$errors): void
    {
        $keysByEntity = [];

        foreach ($fields as $index => $field) {
            $entity = $field['entity'] ?? null;
            $key = $field['key'] ?? null;

            if (! $entity || ! $key) {
                $errors["field_blueprints.{$index}.key"] = __('Field blueprints require entity and key.');
                continue;
            }

            if (in_array($key, Arr::get($keysByEntity, $entity, []), true)) {
                $errors["field_blueprints.{$index}.key"] = __('Field blueprint keys must be unique per entity.');
            }

            $keysByEntity[$entity][] = $key;
        }
    }
}
