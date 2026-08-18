<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductCategoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data, User $user): ProductCategory
    {
        return ProductCategory::query()->create([
            'organization_id' => $organization->id,
            ...$this->payload($organization, $data),
            'created_by' => $user->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProductCategory $category, array $data): ProductCategory
    {
        $category->update($this->payload($category->organization, $data, $category));

        return $category->fresh();
    }

    public function delete(ProductCategory $category): void
    {
        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => [__('Cannot delete a category that has products assigned.')],
            ]);
        }

        $category->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function payload(Organization $organization, array $data, ?ProductCategory $ignore = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));
        $slug = $slug !== '' ? Str::slug($slug) : Str::slug($name);

        if ($slug === '') {
            $slug = 'category-'.Str::lower(Str::random(6));
        }

        $query = ProductCategory::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $slug);

        if ($ignore) {
            $query->whereKeyNot($ignore->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'slug' => [__('This category slug is already in use.')],
            ]);
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
            'is_active' => array_key_exists('is_active', $data)
                ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN)
                : true,
        ];
    }
}
