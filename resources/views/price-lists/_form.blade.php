@php
    $items = old('items', $priceList->relationLoaded('items') ? $priceList->items->map(fn ($item) => [
        'product_id' => $item->product_id,
        'unit_price' => (float) $item->unit_price,
        'min_quantity' => (float) $item->min_quantity,
        'max_quantity' => $item->max_quantity,
        'starts_at' => $item->starts_at?->format('Y-m-d'),
        'ends_at' => $item->ends_at?->format('Y-m-d'),
    ])->all() : [['product_id' => '', 'unit_price' => 0, 'min_quantity' => 0, 'max_quantity' => '', 'starts_at' => '', 'ends_at' => '']]);
    $selectedCustomers = old('customer_ids', $priceList->relationLoaded('customers') ? $priceList->customers->pluck('id')->all() : []);
@endphp
<div class="space-y-6" x-data="{ items: @js($items), add() { this.items.push({ product_id: '', unit_price: 0, min_quantity: 0, max_quantity: '', starts_at: '', ends_at: '' }); }, remove(i) { this.items.splice(i, 1); } }">
    <x-forms.section :title="__('List details')">
        <x-forms.field :label="__('Name')" name="name" required>
            <x-forms.input name="name" :value="old('name', $priceList->name)" required />
        </x-forms.field>
        <x-forms.field :label="__('Currency')" name="currency" required>
            <x-forms.select name="currency" required>
                @foreach (config('price_lists.currencies') as $value => $label)
                    <option value="{{ $value }}" @selected(old('currency', $priceList->currency) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Status')" name="status" required>
            <x-forms.select name="status">
                @foreach (config('price_lists.statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $priceList->status) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.checkbox name="is_default" value="1" :label="__('Default price list')" @checked(old('is_default', $priceList->is_default)) />
        <x-forms.field :label="__('Starts')" name="starts_at">
            <x-forms.input type="date" name="starts_at" :value="old('starts_at', $priceList->starts_at?->format('Y-m-d'))" />
        </x-forms.field>
        <x-forms.field :label="__('Ends')" name="ends_at">
            <x-forms.input type="date" name="ends_at" :value="old('ends_at', $priceList->ends_at?->format('Y-m-d'))" />
        </x-forms.field>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Customers')" name="customer_ids">
                <select name="customer_ids[]" multiple class="w-full rounded-md border-line text-sm">
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(in_array($customer->id, $selectedCustomers, false))>{{ $customer->display_name }}</option>
                    @endforeach
                </select>
            </x-forms.field>
        </div>
    </x-forms.section>

    <x-forms.section :title="__('Quantity prices')">
        <template x-for="(item, index) in items" :key="index">
            <div class="mb-3 grid grid-cols-2 gap-3 md:grid-cols-6">
                <select class="rounded-md border-line text-sm" :name="'items[' + index + '][product_id]'" x-model="item.product_id">
                    <option value="">{{ __('Product') }}</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.01" class="rounded-md border-line text-sm" :name="'items[' + index + '][unit_price]'" x-model="item.unit_price" placeholder="{{ __('Price') }}">
                <input type="number" step="0.01" class="rounded-md border-line text-sm" :name="'items[' + index + '][min_quantity]'" x-model="item.min_quantity" placeholder="{{ __('Min qty') }}">
                <input type="number" step="0.01" class="rounded-md border-line text-sm" :name="'items[' + index + '][max_quantity]'" x-model="item.max_quantity" placeholder="{{ __('Max qty') }}">
                <input type="date" class="rounded-md border-line text-sm" :name="'items[' + index + '][starts_at]'" x-model="item.starts_at">
                <button type="button" class="text-sm text-rose-600" @click="remove(index)">{{ __('Remove') }}</button>
            </div>
        </template>
        <x-ui.button type="button" variant="secondary" size="sm" @click="add()">{{ __('Add price') }}</x-ui.button>
    </x-forms.section>
</div>
