<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\ProductPriceHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PriceListService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data, User $user): PriceList
    {
        return DB::transaction(function () use ($organization, $data, $user) {
            if (! empty($data['is_default'])) {
                $this->clearDefault($organization);
            }

            $list = PriceList::query()->create([
                'organization_id' => $organization->id,
                'name' => $data['name'],
                'currency' => $data['currency'] ?? $organization->currency ?? 'USD',
                'is_default' => (bool) ($data['is_default'] ?? false),
                'status' => $data['status'] ?? 'active',
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->syncItems($list, $data['items'] ?? [], $user);
            $this->syncCustomers($list, $data['customer_ids'] ?? [], $data['customer_priorities'] ?? []);

            return $list->fresh(['items.product', 'customers']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PriceList $list, array $data, User $user): PriceList
    {
        return DB::transaction(function () use ($list, $data, $user) {
            if (! empty($data['is_default'])) {
                $this->clearDefault($list->organization, $list->id);
            }

            $list->update([
                'name' => $data['name'],
                'currency' => $data['currency'] ?? $list->currency,
                'is_default' => (bool) ($data['is_default'] ?? false),
                'status' => $data['status'] ?? $list->status,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncItems($list, $data['items'] ?? [], $user);
            $this->syncCustomers($list, $data['customer_ids'] ?? [], $data['customer_priorities'] ?? []);

            return $list->fresh(['items.product', 'customers']);
        });
    }

    public function delete(PriceList $list): void
    {
        $list->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function syncItems(PriceList $list, array $items, User $user): void
    {
        $existing = $list->items()->get()->keyBy(fn (PriceListItem $item) => $item->product_id.'-'.$item->min_quantity);

        $list->items()->delete();

        foreach ($items as $row) {
            if (empty($row['product_id'])) {
                continue;
            }

            $created = $list->items()->create([
                'product_id' => $row['product_id'],
                'unit_price' => $row['unit_price'],
                'min_quantity' => $row['min_quantity'] ?? 0,
                'max_quantity' => $row['max_quantity'] ?? null,
                'tax_inclusive' => array_key_exists('tax_inclusive', $row) ? (bool) $row['tax_inclusive'] : null,
                'starts_at' => $row['starts_at'] ?? null,
                'ends_at' => $row['ends_at'] ?? null,
            ]);

            $key = $created->product_id.'-'.$created->min_quantity;
            $previous = $existing->get($key);
            $oldPrice = $previous ? (float) $previous->unit_price : null;

            if ($oldPrice === null || round($oldPrice, 2) !== round((float) $created->unit_price, 2)) {
                ProductPriceHistory::query()->create([
                    'organization_id' => $list->organization_id,
                    'product_id' => $created->product_id,
                    'price_list_id' => $list->id,
                    'old_unit_price' => $oldPrice,
                    'new_unit_price' => $created->unit_price,
                    'changed_by' => $user->id,
                ]);
            }
        }
    }

    /**
     * @param  array<int, int|string>  $customerIds
     * @param  array<int|string, int|string>  $priorities
     */
    protected function syncCustomers(PriceList $list, array $customerIds, array $priorities = []): void
    {
        $sync = [];
        foreach ($customerIds as $id) {
            if (! $id) {
                continue;
            }
            $sync[(int) $id] = ['priority' => (int) ($priorities[$id] ?? 0)];
        }

        $list->customers()->sync($sync);
    }

    protected function clearDefault(Organization $organization, ?int $exceptId = null): void
    {
        PriceList::query()
            ->where('organization_id', $organization->id)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->update(['is_default' => false]);
    }
}
