<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\TaskStatus;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TaskStatusService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data): TaskStatus
    {
        $payload = $this->validatedPayload($organization, $data);

        $status = TaskStatus::query()->create([
            'organization_id' => $organization->id,
            ...$payload,
        ]);

        if ($status->is_default) {
            $this->unsetOtherDefaults($status);
        }

        return $status->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TaskStatus $status, array $data): TaskStatus
    {
        $payload = $this->validatedPayload($status->organization, $data, $status);

        $status->update($payload);

        if ($status->is_default) {
            $this->unsetOtherDefaults($status);
        }

        return $status->fresh();
    }

    public function delete(TaskStatus $status): void
    {
        if ($status->tasks()->exists()) {
            throw ValidationException::withMessages([
                'status' => __('Cannot delete a status that is used by tasks.'),
            ]);
        }

        if ($status->is_default) {
            throw ValidationException::withMessages([
                'status' => __('The default task status cannot be deleted.'),
            ]);
        }

        $openStatuses = TaskStatus::query()
            ->where('organization_id', $status->organization_id)
            ->where('is_closed', false)
            ->whereKeyNot($status->id)
            ->count();

        if (! $status->is_closed && $openStatuses === 0) {
            throw ValidationException::withMessages([
                'status' => __('At least one open task status must remain.'),
            ]);
        }

        $status->delete();
    }

    protected function unsetOtherDefaults(TaskStatus $status): void
    {
        TaskStatus::query()
            ->where('organization_id', $status->organization_id)
            ->whereKeyNot($status->id)
            ->update(['is_default' => false]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function validatedPayload(Organization $organization, array $data, ?TaskStatus $ignore = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => __('A status name is required.'),
            ]);
        }

        $slug = trim((string) ($data['slug'] ?? ''));
        $slug = $slug !== '' ? Str::slug($slug) : Str::slug($name);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'slug' => __('A valid status slug is required.'),
            ]);
        }

        $slugQuery = TaskStatus::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $slug);

        if ($ignore) {
            $slugQuery->whereKeyNot($ignore->id);
        }

        if ($slugQuery->exists()) {
            throw ValidationException::withMessages([
                'slug' => __('This status slug is already in use.'),
            ]);
        }

        $isClosed = array_key_exists('is_closed', $data) ? (bool) $data['is_closed'] : ($ignore?->is_closed ?? false);
        $isDefault = array_key_exists('is_default', $data) ? (bool) $data['is_default'] : ($ignore?->is_default ?? false);

        if ($isDefault && $isClosed) {
            throw ValidationException::withMessages([
                'is_default' => __('A closed status cannot be set as the default task status.'),
            ]);
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'color' => $data['color'] ?? null,
            'is_default' => $isDefault,
            'is_closed' => $isClosed,
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : ($ignore?->sort_order ?? 0),
        ];
    }
}
