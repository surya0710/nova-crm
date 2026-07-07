<?php

namespace App\Services;

class LeadNormalizationService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalize(array $payload): array
    {
        $normalized = [];

        foreach ($payload as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $value = $value === '' ? null : $value;
            }

            $normalized[$key] = $value;
        }

        if (isset($normalized['phone'])) {
            $normalized['phone'] = $this->normalizePhone($normalized['phone']);
        }

        if (isset($normalized['email']) && is_string($normalized['email'])) {
            $normalized['email'] = strtolower($normalized['email']);
        }

        if (isset($normalized['source']) && is_string($normalized['source'])) {
            $normalized['source'] = $this->normalizeSource($normalized['source']);
        }

        $customFields = $normalized['custom_fields'] ?? [];
        if (! is_array($customFields)) {
            $customFields = [];
        }

        foreach (['form_type', 'source_url', 'service_interest'] as $field) {
            if (! empty($normalized[$field])) {
                $customFields[$field] = $normalized[$field];
            }
        }

        if (isset($customFields['visa_type'])) {
            $customFields['visa_type'] = $this->normalizeVisaType($customFields['visa_type']);
        }

        if (isset($normalized['service_interest'])) {
            $customFields['service_interest'] = $this->normalizeVisaType($normalized['service_interest']);
        }

        $normalized['custom_fields'] = $customFields !== [] ? $customFields : null;

        unset($normalized['form_type'], $normalized['source_url'], $normalized['service_interest']);

        if (empty($normalized['source'])) {
            $normalized['source'] = 'api';
        }

        return $normalized;
    }

    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $phone = trim($phone);
        $hasPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        return ($hasPlus ? '+' : '').$digits;
    }

    public function normalizeVisaType(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $key = strtolower(trim($value));
        $mappings = config('leads.intake_normalizations.visa_types', []);

        return $mappings[$key] ?? $value;
    }

    public function normalizeSource(string $source): string
    {
        $key = strtolower(str_replace([' ', '-'], '_', trim($source)));
        $sources = config('leads.sources', []);

        if (array_key_exists($key, $sources)) {
            return $key;
        }

        if (array_key_exists($source, $sources)) {
            return $source;
        }

        return $source;
    }
}
