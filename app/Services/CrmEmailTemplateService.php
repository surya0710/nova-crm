<?php

namespace App\Services;

use App\Models\CrmEmailTemplate;
use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\ModuleSubscriptionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CrmEmailTemplateService
{
    public function __construct(
        protected CrmEmailVariableRenderer $renderer,
        protected ModuleSubscriptionService $modules,
    ) {}

    /**
     * @return array<string, string>
     */
    public function categoriesFor(Organization $organization): array
    {
        $categories = config('crm_email.categories', []);
        $licenses = config('crm_email.category_license', []);

        return collect($categories)
            ->filter(function (string $label, string $key) use ($organization, $licenses) {
                $license = $licenses[$key] ?? null;

                return $license === null || $this->modules->moduleAllowed($organization, $license);
            })
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function modulesFor(Organization $organization): array
    {
        $modules = config('crm_email.modules', []);

        return collect($modules)
            ->filter(function (string $label, string $key) use ($organization) {
                $license = $key === 'hrms' ? 'hrms' : 'crm';

                return $this->modules->moduleAllowed($organization, $license);
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data, User $actor): CrmEmailTemplate
    {
        $this->assertCategory($organization, $data['category'] ?? 'general');

        return CrmEmailTemplate::query()->create([
            'organization_id' => $organization->id,
            'name' => trim((string) $data['name']),
            'subject' => trim((string) $data['subject']),
            'body' => trim((string) $data['body']),
            'category' => $data['category'] ?? 'general',
            'is_active' => array_key_exists('is_active', $data)
                ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN)
                : true,
            'available_modules' => $this->normalizeModules($organization, $data['available_modules'] ?? null, $data['category'] ?? 'general'),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CrmEmailTemplate $template, array $data, User $actor): CrmEmailTemplate
    {
        $category = $data['category'] ?? $template->category;
        $this->assertCategory($template->organization, $category);

        $template->update([
            'name' => trim((string) ($data['name'] ?? $template->name)),
            'subject' => trim((string) ($data['subject'] ?? $template->subject)),
            'body' => trim((string) ($data['body'] ?? $template->body)),
            'category' => $category,
            'is_active' => array_key_exists('is_active', $data)
                ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN)
                : $template->is_active,
            'available_modules' => array_key_exists('available_modules', $data)
                ? $this->normalizeModules($template->organization, $data['available_modules'], $category)
                : $template->available_modules,
            'updated_by' => $actor->id,
        ]);

        return $template->fresh();
    }

    /**
     * @return Collection<int, array{id: int, name: string, subject: string, body: string}>
     */
    public function forComposer(Organization $organization, ?string $module = null, ?Model $related = null): Collection
    {
        $context = $this->renderer->contextFor($organization, $related);

        return CrmEmailTemplate::query()
            ->active()
            ->forModule($module)
            ->orderBy('name')
            ->get()
            ->map(fn (CrmEmailTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'subject' => $this->renderer->interpolate($template->subject, $context),
                'body' => $this->renderer->interpolate($template->body, $context),
            ]);
    }

    protected function assertCategory(Organization $organization, string $category): void
    {
        if (! array_key_exists($category, $this->categoriesFor($organization))) {
            throw ValidationException::withMessages([
                'category' => __('The selected category is invalid.'),
            ]);
        }
    }

    /**
     * @param  mixed  $modules
     * @return list<string>
     */
    protected function normalizeModules(Organization $organization, mixed $modules, string $category): array
    {
        $allowed = array_keys($this->modulesFor($organization));
        $defaults = config('crm_email.category_modules.'.$category, ['customers']);

        $selected = is_array($modules)
            ? $modules
            : (filled($modules) ? [$modules] : $defaults);

        return collect($selected)
            ->map(fn ($module) => (string) $module)
            ->filter(fn ($module) => in_array($module, $allowed, true))
            ->unique()
            ->values()
            ->all();
    }
}
