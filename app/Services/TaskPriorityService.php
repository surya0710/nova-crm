<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\TaskPriority;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TaskPriorityService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data): TaskPriority
    {
        $payload = $this->validatedPayload($organization, $data);

        $priority = TaskPriority::query()->create([
            'organization_id' => $organization->id,
            ...$payload,
        ]);

        if ($priority->is_default) {
            $this->unsetOtherDefaults($priority);
        }

        return $priority->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TaskPriority $priority, array $data): TaskPriority
    {
        $payload = $this->validatedPayload($priority->organization, $data, $priority);

        $priority->update($payload);

        if ($priority->is_default) {
            $this->unsetOtherDefaults($priority);
        }

        return $priority->fresh();
    }

    public function delete(TaskPriority $priority): void
    {
        if ($priority->tasks()->exists()) {
            throw ValidationException::withMessages([
                'priority' => __('Cannot delete a priority that is used by tasks.'),
            ]);
        }

        if ($priority->is_default) {
            throw ValidationException::withMessages([
                'priority' => __('The default task priority cannot be deleted.'),
            ]);
        }

        $remaining = TaskPriority::query()
            ->where('organization_id', $priority->organization_id)
            ->whereKeyNot($priority->id)
            ->count();

        if ($remaining === 0) {
            throw ValidationException::withMessages([
                'priority' => __('At least one task priority must remain.'),
            ]);
        }

        $priority->delete();
    }

    public function makeDefault(TaskPriority $priority): TaskPriority
    {
        $priority->update(['is_default' => true]);
        $this->unsetOtherDefaults($priority);

        return $priority->fresh();
    }

    protected function unsetOtherDefaults(TaskPriority $priority): void
    {
        TaskPriority::query()
            ->where('organization_id', $priority->organization_id)
            ->whereKeyNot($priority->id)
            ->update(['is_default' => false]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function validatedPayload(Organization $organization, array $data, ?TaskPriority $ignore = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => __('A priority name is required.'),
            ]);
        }

        $slug = trim((string) ($data['slug'] ?? ''));
        $slug = $slug !== '' ? Str::slug($slug) : Str::slug($name);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'slug' => __('A valid priority slug is required.'),
            ]);
        }

        $slugQuery = TaskPriority::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $slug);

        if ($ignore) {
            $slugQuery->whereKeyNot($ignore->id);
        }

        if ($slugQuery->exists()) {
            throw ValidationException::withMessages([
                'slug' => __('This priority slug is already in use.'),
            ]);
        }

        $level = isset($data['level']) ? (int) $data['level'] : ($ignore?->level ?? 1);

        if ($level < 1) {
            throw ValidationException::withMessages([
                'level' => __('Priority level must be at least 1.'),
            ]);
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'color' => $data['color'] ?? null,
            'level' => $level,
            'is_default' => array_key_exists('is_default', $data)
                ? (bool) $data['is_default']
                : ($ignore?->is_default ?? false),
        ];
    }
}
