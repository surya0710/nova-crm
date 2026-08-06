<?php

namespace App\Services\Platform;

use App\Models\IndustryTemplate;
use App\Models\IndustryTemplateVersion;
use App\Models\PlatformUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IndustryTemplateService
{
    public function __construct(
        protected IndustryTemplatePayloadValidator $payloadValidator,
        protected PlatformAuditService $audit,
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = IndustryTemplate::query()
            ->with(['currentVersion', 'creator'])
            ->withCount('applications');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('industry', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        return $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, PlatformUser $actor): IndustryTemplate
    {
        $payload = $this->payloadFromInput($data['draft_payload'] ?? null);

        $template = IndustryTemplate::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['slug'] ?? $data['name']),
            'industry' => $data['industry'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => 'draft',
            'visibility' => $data['visibility'] ?? 'internal',
            'sort_order' => $data['sort_order'] ?? 0,
            'draft_payload' => array_replace($this->payloadValidator->defaultPayload(), $payload),
            'draft_schema_version' => (int) config('industry_templates.schema_version'),
            'created_by_platform_user_id' => $actor->id,
            'updated_by_platform_user_id' => $actor->id,
        ]);

        $this->audit->log('industry_template.created', $actor, null, [
            'template_id' => $template->id,
            'template_name' => $template->name,
        ], IndustryTemplate::class.':'.$template->id);

        return $template;
    }

    public function updateDraft(IndustryTemplate $template, array $data, PlatformUser $actor): IndustryTemplate
    {
        $payload = $this->payloadFromInput($data['draft_payload'] ?? null);

        $template->update([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['slug'] ?? $data['name'], $template),
            'industry' => $data['industry'] ?? null,
            'description' => $data['description'] ?? null,
            'visibility' => $data['visibility'] ?? 'internal',
            'sort_order' => $data['sort_order'] ?? 0,
            'draft_payload' => array_replace($this->payloadValidator->defaultPayload(), $payload),
            'draft_schema_version' => (int) config('industry_templates.schema_version'),
            'updated_by_platform_user_id' => $actor->id,
        ]);

        $this->audit->log('industry_template.updated', $actor, null, [
            'template_id' => $template->id,
            'template_name' => $template->name,
        ], IndustryTemplate::class.':'.$template->id);

        return $template->fresh(['currentVersion']);
    }

    public function cloneVersion(IndustryTemplateVersion $version, array $data, PlatformUser $actor): IndustryTemplate
    {
        $template = $this->create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $data['name'],
            'industry' => $data['industry'] ?? $version->template->industry,
            'description' => $data['description'] ?? $version->template->description,
            'visibility' => $data['visibility'] ?? 'internal',
            'sort_order' => $data['sort_order'] ?? 0,
            'draft_payload' => json_encode($version->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ], $actor);

        $this->audit->log('industry_template.cloned', $actor, null, [
            'template_id' => $template->id,
            'source_template_id' => $version->industry_template_id,
            'source_version_id' => $version->id,
        ], IndustryTemplate::class.':'.$template->id);

        return $template;
    }

    public function inactivate(IndustryTemplate $template, PlatformUser $actor): IndustryTemplate
    {
        $template->update([
            'status' => 'inactive',
            'updated_by_platform_user_id' => $actor->id,
        ]);

        $this->audit->log('industry_template.inactivated', $actor, null, [
            'template_id' => $template->id,
            'template_name' => $template->name,
        ], IndustryTemplate::class.':'.$template->id);

        return $template->fresh(['currentVersion']);
    }

    public function archive(IndustryTemplate $template, PlatformUser $actor): IndustryTemplate
    {
        $template->update([
            'status' => 'archived',
            'archived_by_platform_user_id' => $actor->id,
            'archived_at' => now(),
            'updated_by_platform_user_id' => $actor->id,
        ]);

        $this->audit->log('industry_template.archived', $actor, null, [
            'template_id' => $template->id,
            'template_name' => $template->name,
        ], IndustryTemplate::class.':'.$template->id);

        return $template->fresh(['currentVersion']);
    }

    public function reactivate(IndustryTemplate $template, PlatformUser $actor): IndustryTemplate
    {
        $template->update([
            'status' => $template->current_version_id ? 'published' : 'draft',
            'archived_by_platform_user_id' => null,
            'archived_at' => null,
            'updated_by_platform_user_id' => $actor->id,
        ]);

        $this->audit->log('industry_template.reactivated', $actor, null, [
            'template_id' => $template->id,
            'template_name' => $template->name,
        ], IndustryTemplate::class.':'.$template->id);

        return $template->fresh(['currentVersion']);
    }

    public function payloadFromInput(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return $this->payloadValidator->defaultPayload();
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'draft_payload' => __('Draft payload must be valid JSON.'),
            ]);
        }

        return $decoded;
    }

    protected function uniqueSlug(string $value, ?IndustryTemplate $ignore = null): string
    {
        $slug = Str::slug($value);
        $base = $slug !== '' ? $slug : 'industry-template';
        $slug = $base;
        $count = 1;

        while (IndustryTemplate::query()
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$count;
            $count++;
        }

        return $slug;
    }
}
