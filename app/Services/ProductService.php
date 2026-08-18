<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProductService
{
    public function __construct(protected MetadataEntityFormService $metadataForms) {}

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function searchQuery(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);
        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $searchQuery) use ($search) {
            $searchQuery->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('hsn_sac', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function applySort(Builder $query, ?string $sort, string $direction = 'asc'): Builder
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        return match ($sort) {
            'name' => $query->orderBy('name', $direction),
            'sku' => $query->orderBy('sku', $direction),
            'unit_price' => $query->orderBy('unit_price', $direction),
            'category' => $query->orderBy('category', $direction),
            'type' => $query->orderBy('type', $direction),
            default => $query->latest(),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $metadataValues
     */
    public function create(Organization $organization, array $data, User $user, array $metadataValues = []): Product
    {
        $product = Product::query()->create([
            ...$this->syncCategoryName($organization, $data),
            'created_by' => $user->id,
        ]);

        $this->metadataForms->persistValidatedValues($product, $metadataValues);

        return $product->fresh(['productCategory', 'creator']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $metadataValues
     */
    public function update(Product $product, array $data, array $metadataValues = []): Product
    {
        $product->update($this->syncCategoryName($product->organization, $data));
        $this->metadataForms->persistValidatedValues($product, $metadataValues);

        return $product->fresh(['productCategory', 'creator']);
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function syncCategoryName(Organization $organization, array $data): array
    {
        if (! empty($data['product_category_id'])) {
            $category = ProductCategory::query()
                ->where('organization_id', $organization->id)
                ->find($data['product_category_id']);

            if ($category) {
                $data['category'] = $category->name;
            }
        }

        return $data;
    }
}
