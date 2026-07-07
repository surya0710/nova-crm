@php
    $defaultItems = [
        [
            'product_id' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'tax_rate' => 0,
            'discount_percent' => 0,
        ],
    ];

    $existingItems = $quotation->exists
        ? $quotation->items->map(fn ($item) => [
            'product_id' => $item->product_id ?? '',
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'tax_rate' => (float) $item->tax_rate,
            'discount_percent' => (float) $item->discount_percent,
        ])->values()->all()
        : $defaultItems;

    $initialItems = old('items', $existingItems);
    $productOptions = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'unit_price' => (float) $product->unit_price,
        'tax_rate' => (float) $product->tax_rate,
    ])->values();
@endphp

<div class="space-y-8">
    <div>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Quotation Details') }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <x-input-label for="customer_id" :value="__('Customer')" />
                <select id="customer_id" name="customer_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                    <option value="">{{ __('Select customer') }}</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id', $quotation->customer_id) === (string) $customer->id)>{{ $customer->display_name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="opportunity_id" :value="__('Deal (optional)')" />
                <select id="opportunity_id" name="opportunity_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($opportunities as $opportunity)
                        <option value="{{ $opportunity->id }}" @selected((string) old('opportunity_id', $quotation->opportunity_id) === (string) $opportunity->id)>
                            {{ $opportunity->title }}@if($opportunity->customer) · {{ $opportunity->customer->display_name }}@endif
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('opportunity_id')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="title" :value="__('Title')" />
                <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title', $quotation->title)" placeholder="{{ __('Website redesign proposal') }}" />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="issue_date" :value="__('Issue Date')" />
                <x-text-input id="issue_date" class="block mt-1 w-full" type="date" name="issue_date" :value="old('issue_date', $quotation->issue_date?->format('Y-m-d'))" required />
                <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="valid_until" :value="__('Valid Until')" />
                <x-text-input id="valid_until" class="block mt-1 w-full" type="date" name="valid_until" :value="old('valid_until', $quotation->valid_until?->format('Y-m-d'))" />
                <x-input-error :messages="$errors->get('valid_until')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="currency" :value="__('Currency')" />
                <select id="currency" name="currency" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                    @foreach (config('quotations.currencies') as $value => $label)
                        <option value="{{ $value }}" @selected(old('currency', $quotation->currency) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="status" :value="__('Status')" />
                <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                    @foreach (config('quotations.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $quotation->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="notes" :value="__('Terms & Notes')" />
                <textarea id="notes" name="notes" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="{{ __('Payment terms, delivery timeline…') }}">{{ old('notes', $quotation->notes) }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
        </div>
    </div>

    <div
        x-data="{
            items: @js($initialItems),
            products: @js($productOptions),
            addItem() {
                this.items.push({ product_id: '', description: '', quantity: 1, unit_price: 0, tax_rate: 0, discount_percent: 0 });
            },
            removeItem(index) {
                if (this.items.length > 1) {
                    this.items.splice(index, 1);
                }
            },
            selectProduct(index, productId) {
                const product = this.products.find(p => String(p.id) === String(productId));
                if (! product) return;
                this.items[index].product_id = product.id;
                this.items[index].description = product.name;
                this.items[index].unit_price = product.unit_price;
                this.items[index].tax_rate = product.tax_rate;
            },
            lineSubtotal(item) {
                return (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
            },
            lineDiscount(item) {
                return this.lineSubtotal(item) * ((parseFloat(item.discount_percent) || 0) / 100);
            },
            lineTax(item) {
                const taxable = this.lineSubtotal(item) - this.lineDiscount(item);
                return taxable * ((parseFloat(item.tax_rate) || 0) / 100);
            },
            lineTotal(item) {
                return this.lineSubtotal(item) - this.lineDiscount(item) + this.lineTax(item);
            },
            get subtotal() {
                return this.items.reduce((sum, item) => sum + this.lineSubtotal(item), 0);
            },
            get discountTotal() {
                return this.items.reduce((sum, item) => sum + this.lineDiscount(item), 0);
            },
            get taxTotal() {
                return this.items.reduce((sum, item) => sum + this.lineTax(item), 0);
            },
            get grandTotal() {
                return this.subtotal - this.discountTotal + this.taxTotal;
            },
            format(value) {
                return (parseFloat(value) || 0).toFixed(2);
            }
        }"
    >
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-sm font-semibold text-slate-900">{{ __('Line Items') }}</h4>
            <button type="button" @click="addItem()" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                + {{ __('Add line') }}
            </button>
        </div>

        <x-input-error :messages="$errors->get('items')" class="mb-3" />

        <div class="space-y-4">
            <template x-for="(item, index) in items" :key="index">
                <div class="rounded-lg border border-slate-200 p-4 bg-slate-50/50">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
                        <div class="lg:col-span-3">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ __('Product') }}</label>
                            <select
                                class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                x-model="item.product_id"
                                @change="selectProduct(index, $event.target.value)"
                            >
                                <option value="">{{ __('Custom line') }}</option>
                                <template x-for="product in products" :key="product.id">
                                    <option :value="product.id" x-text="product.name"></option>
                                </template>
                            </select>
                            <input type="hidden" :name="'items[' + index + '][product_id]'" x-model="item.product_id">
                        </div>
                        <div class="lg:col-span-4">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ __('Description') }}</label>
                            <input
                                type="text"
                                class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                :name="'items[' + index + '][description]'"
                                x-model="item.description"
                                required
                            >
                        </div>
                        <div class="lg:col-span-1">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ __('Qty') }}</label>
                            <input type="number" step="0.01" min="0.01" class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" :name="'items[' + index + '][quantity]'" x-model="item.quantity" required>
                        </div>
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ __('Unit Price') }}</label>
                            <input type="number" step="0.01" min="0" class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" :name="'items[' + index + '][unit_price]'" x-model="item.unit_price" required>
                        </div>
                        <div class="lg:col-span-1">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ __('Tax %') }}</label>
                            <input type="number" step="0.01" min="0" max="100" class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" :name="'items[' + index + '][tax_rate]'" x-model="item.tax_rate">
                        </div>
                        <div class="lg:col-span-1">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ __('Disc %') }}</label>
                            <input type="number" step="0.01" min="0" max="100" class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" :name="'items[' + index + '][discount_percent]'" x-model="item.discount_percent">
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <button type="button" @click="removeItem(index)" class="text-xs text-red-600 hover:text-red-800" x-show="items.length > 1">{{ __('Remove') }}</button>
                        <p class="text-sm text-slate-600 ml-auto">{{ __('Line total') }}: <span class="font-semibold text-slate-900" x-text="format(lineTotal(item))"></span></p>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-4 max-w-sm ml-auto">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Subtotal') }}</dt>
                    <dd class="font-medium text-slate-900" x-text="format(subtotal)"></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Discount') }}</dt>
                    <dd class="font-medium text-slate-900" x-text="format(discountTotal)"></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('Tax') }}</dt>
                    <dd class="font-medium text-slate-900" x-text="format(taxTotal)"></dd>
                </div>
                <div class="flex justify-between border-t border-slate-100 pt-2">
                    <dt class="font-semibold text-slate-900">{{ __('Total') }}</dt>
                    <dd class="font-bold text-slate-900" x-text="format(grandTotal)"></dd>
                </div>
            </dl>
        </div>
    </div>
</div>
