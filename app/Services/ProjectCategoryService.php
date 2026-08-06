<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\ProjectCategory;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectCategoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data): ProjectCategory
    {
        $payload = $this->validatedPayload($organization, $data);

        return ProjectCategory::query()->create([
            'organization_id' => $organization->id,
            ...$payload,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectCategory $category, array $data): ProjectCategory
    {
        $payload = $this->validatedPayload($category->organization, $data, $category);

        $category->update($payload);

        return $category->fresh();
    }

    public function activate(ProjectCategory $category): ProjectCategory
    {
        $category->update(['is_active' => true]);

        return $category->fresh();
    }

    public function deactivate(ProjectCategory $category): ProjectCategory
    {
        $category->update(['is_active' => false]);

        return $category->fresh();
    }

    public function delete(ProjectCategory $category): void
    {
        if ($category->is_system) {
            throw ValidationException::withMessages([
                'category' => __('System categories cannot be deleted.'),
            ]);
        }

        if ($category->projects()->exists()) {
            throw ValidationException::withMessages([
                'category' => __('Cannot delete a category that has projects assigned.'),
            ]);
        }

        $category->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function validatedPayload(Organization $organization, array $data, ?ProjectCategory $ignore = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => __('A category name is required.'),
            ]);
        }

        $slug = trim((string) ($data['slug'] ?? ''));
        $slug = $slug !== '' ? Str::slug($slug) : Str::slug($name);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'slug' => __('A valid category slug is required.'),
            ]);
        }

        $slugQuery = ProjectCategory::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $slug);

        if ($ignore) {
            $slugQuery->whereKeyNot($ignore->id);
        }

        if ($slugQuery->exists()) {
            throw ValidationException::withMessages([
                'slug' => __('This category slug is already in use.'),
            ]);
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
        ];
    }
}
