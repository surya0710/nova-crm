<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\ProjectType;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectTypeService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data): ProjectType
    {
        $payload = $this->validatedPayload($organization, $data);

        return ProjectType::query()->create([
            'organization_id' => $organization->id,
            ...$payload,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectType $type, array $data): ProjectType
    {
        $payload = $this->validatedPayload($type->organization, $data, $type);

        $type->update($payload);

        return $type->fresh();
    }

    public function delete(ProjectType $type): void
    {
        if ($type->is_system) {
            throw ValidationException::withMessages([
                'type' => __('System project types cannot be deleted.'),
            ]);
        }

        if ($type->projects()->exists()) {
            throw ValidationException::withMessages([
                'type' => __('Cannot delete a project type that has projects assigned.'),
            ]);
        }

        $type->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function validatedPayload(Organization $organization, array $data, ?ProjectType $ignore = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => __('A project type name is required.'),
            ]);
        }

        $slug = trim((string) ($data['slug'] ?? ''));
        $slug = $slug !== '' ? Str::slug($slug) : Str::slug($name);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'slug' => __('A valid project type slug is required.'),
            ]);
        }

        $slugQuery = ProjectType::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $slug);

        if ($ignore) {
            $slugQuery->whereKeyNot($ignore->id);
        }

        if ($slugQuery->exists()) {
            throw ValidationException::withMessages([
                'slug' => __('This project type slug is already in use.'),
            ]);
        }

        $defaultDuration = $data['default_duration'] ?? null;

        if ($defaultDuration !== null && $defaultDuration !== '') {
            $defaultDuration = (int) $defaultDuration;

            if ($defaultDuration < 1) {
                throw ValidationException::withMessages([
                    'default_duration' => __('Default duration must be a positive integer.'),
                ]);
            }
        } else {
            $defaultDuration = null;
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'default_duration' => $defaultDuration,
            'color' => $data['color'] ?? null,
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
        ];
    }
}
