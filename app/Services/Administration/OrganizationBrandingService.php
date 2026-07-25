<?php

namespace App\Services\Administration;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationLogoService;
use Illuminate\Http\UploadedFile;

class OrganizationBrandingService
{
    public function __construct(
        protected OrganizationLogoService $logos,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function branding(Organization $organization): array
    {
        $brand = is_array($organization->settings['branding'] ?? null)
            ? $organization->settings['branding']
            : [];

        return [
            'primary_color' => $brand['primary_color'] ?? '',
            'accent_color' => $brand['accent_color'] ?? '',
            'email_from_name' => $brand['email_from_name'] ?? ($organization->settings['mail']['from_name'] ?? $organization->name),
            'email_header_text' => $brand['email_header_text'] ?? $organization->name,
            'login_headline' => $brand['login_headline'] ?? '',
            'login_tagline' => $brand['login_tagline'] ?? '',
            'document_footer' => $brand['document_footer'] ?? '',
            'logo_url' => $organization->logo_url,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Organization $organization, array $input, User $actor, ?UploadedFile $logo = null, bool $removeLogo = false): void
    {
        $settings = $organization->settings ?? [];
        $settings['branding'] = [
            'primary_color' => $this->normalizeColor($input['primary_color'] ?? null),
            'accent_color' => $this->normalizeColor($input['accent_color'] ?? null),
            'email_from_name' => trim((string) ($input['email_from_name'] ?? '')) ?: null,
            'email_header_text' => trim((string) ($input['email_header_text'] ?? '')) ?: null,
            'login_headline' => trim((string) ($input['login_headline'] ?? '')) ?: null,
            'login_tagline' => trim((string) ($input['login_tagline'] ?? '')) ?: null,
            'document_footer' => trim((string) ($input['document_footer'] ?? '')) ?: null,
            'updated_by' => $actor->id,
            'updated_at' => now()->toIso8601String(),
        ];

        $data = ['settings' => $settings];

        if ($removeLogo) {
            $this->logos->delete($organization);
            $data['logo'] = null;
        }

        if ($logo) {
            $data['logo'] = $this->logos->store($organization, $logo);
        }

        $organization->update($data);
    }

    protected function normalizeColor(?string $color): ?string
    {
        $color = trim((string) $color);
        if ($color === '') {
            return null;
        }

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return $color;
        }

        return null;
    }
}
