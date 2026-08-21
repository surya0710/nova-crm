<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DiscountRule;
use App\Models\Organization;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use Illuminate\Support\Carbon;

class PriceResolutionService
{
    /**
     * @return array{
     *     unit_price: float,
     *     discount_percent: float,
     *     tax_rate: float,
     *     tax_inclusive: bool,
     *     source: string,
     *     price_list_id: ?int,
     *     price_list_name: ?string
     * }
     */
    public function resolve(Product $product, ?Customer $customer = null, float $quantity = 1, ?Carbon $on = null): array
    {
        $on = $on ?? now();
        $item = $this->matchingItem($product, $customer, $quantity, $on);
        $unitPrice = $item
            ? (float) $item->unit_price
            : (float) $product->unit_price;
        $taxInclusive = $item && $item->tax_inclusive !== null
            ? (bool) $item->tax_inclusive
            : (bool) $product->tax_inclusive;
        $discount = $this->discountPercent($product, $customer, $unitPrice, $quantity, $on);

        return [
            'unit_price' => $unitPrice,
            'discount_percent' => $discount,
            'tax_rate' => (float) $product->tax_rate,
            'tax_inclusive' => $taxInclusive,
            'cess_rate' => (float) $product->cess_rate,
            'source' => $item ? 'price_list' : 'catalog',
            'price_list_id' => $item?->price_list_id,
            'price_list_name' => $item?->priceList?->name,
        ];
    }

    protected function matchingItem(Product $product, ?Customer $customer, float $quantity, Carbon $on): ?PriceListItem
    {
        $lists = $this->candidateLists($product->organization, $customer, $on);

        foreach ($lists as $list) {
            $matches = $list->items
                ->where('product_id', $product->id)
                ->filter(fn (PriceListItem $item) => $item->isEffective($on) && $item->matchesQuantity($quantity))
                ->sortByDesc(fn (PriceListItem $item) => (float) $item->min_quantity);

            $best = $matches->first();
            if ($best) {
                $best->setRelation('priceList', $list);

                return $best;
            }
        }

        return null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, PriceList>
     */
    protected function candidateLists(Organization $organization, ?Customer $customer, Carbon $on)
    {
        $lists = collect();

        if ($customer) {
            $customer->loadMissing('priceLists.items');
            $lists = $customer->priceLists
                ->filter(fn (PriceList $list) => $list->isActive($on))
                ->sortByDesc(fn (PriceList $list) => (int) ($list->pivot->priority ?? 0))
                ->values();
        }

        $default = PriceList::query()
            ->with('items')
            ->where('organization_id', $organization->id)
            ->where('is_default', true)
            ->get()
            ->filter(fn (PriceList $list) => $list->isActive($on));

        return $lists->concat($default)->unique('id')->values();
    }

    protected function discountPercent(Product $product, ?Customer $customer, float $unitPrice, float $quantity, Carbon $on): float
    {
        $catalog = (float) $product->default_discount_percent;
        $rules = DiscountRule::query()
            ->where('organization_id', $product->organization_id)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get()
            ->filter(function (DiscountRule $rule) use ($product, $customer, $on) {
                if (! $rule->isEffective($on)) {
                    return false;
                }
                if ($rule->product_id && (int) $rule->product_id !== (int) $product->id) {
                    return false;
                }
                if ($rule->customer_id && (! $customer || (int) $rule->customer_id !== (int) $customer->id)) {
                    return false;
                }

                return true;
            });

        $best = $catalog;

        foreach ($rules as $rule) {
            $best = max($best, $rule->discountFor($unitPrice, $quantity));
        }

        return min(100.0, $best);
    }
}
