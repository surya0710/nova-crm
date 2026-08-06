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
    <x-forms.section :title="__('Quotation Details')">
        <x-forms.field :label="__('Customer')" name="customer_id" required>
            <x-forms.select id="customer_id" name="customer_id" required>
                <option value="">{{ __('Select customer') }}</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $quotation->customer_id) === (string) $customer->id)>{{ $customer->display_name }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Deal (optional)')" name="opportunity_id">
            <x-forms.select id="opportunity_id" name="opportunity_id">
                <option value="">{{ __('None') }}</option>
                @foreach ($opportunities as $opportunity)
                    <option value="{{ $opportunity->id }}" @selected((string) old('opportunity_id', $quotation->opportunity_id) === (string) $opportunity->id)>
                        {{ $opportunity->title }}@if($opportunity->customer) · {{ $opportunity->customer->display_name }}@endif
                    </option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Title')" name="title">
                <x-forms.input id="title" type="text" name="title" :value="old('title', $quotation->title)" placeholder="{{ __('Website redesign proposal') }}" />
            </x-forms.field>
        </div>
        <x-forms.field :label="__('Issue Date')" name="issue_date" required>
            <x-forms.input id="issue_date" type="date" name="issue_date" :value="old('issue_date', $quotation->issue_date?->format('Y-m-d'))" required />
        </x-forms.field>
        <x-forms.field :label="__('Valid Until')" name="valid_until">
            <x-forms.input id="valid_until" type="date" name="valid_until" :value="old('valid_until', $quotation->valid_until?->format('Y-m-d'))" />
        </x-forms.field>
        <x-forms.field :label="__('Currency')" name="currency" required>
            <x-forms.select id="currency" name="currency" required>
                @foreach (config('quotations.currencies') as $value => $label)
                    <option value="{{ $value }}" @selected(old('currency', $quotation->currency) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <div>
            @if ($quotation->exists)
                <p class="text-xs font-medium text-ink-muted">{{ __('Status') }}</p>
                <p class="mt-1 text-sm text-ink-heading">{{ $quotation->status_label }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ __('Status is managed from the quotation detail page.') }}</p>
            @else
                <input type="hidden" name="status" value="draft">
                <p class="text-xs font-medium text-ink-muted">{{ __('Status') }}</p>
                <p class="mt-1 text-sm text-ink-heading">{{ config('quotations.statuses.draft') }}</p>
            @endif
        </div>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Terms & Notes')" name="notes">
                <x-forms.textarea id="notes" name="notes" rows="3" placeholder="{{ __('Payment terms, delivery timeline…') }}">{{ old('notes', $quotation->notes) }}</x-forms.textarea>
            </x-forms.field>
        </div>
    </x-forms.section>

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
        <div class="mb-4 flex items-center justify-between">
            <h4 class="text-sm font-semibold text-ink-heading">{{ __('Line Items') }}</h4>
            <button type="button" @click="addItem()" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                + {{ __('Add line') }}
            </button>
        </div>

        <x-input-error :messages="$errors->get('items')" class="mb-3" />

        <div class="space-y-4">
            <template x-for="(item, index) in items" :key="index">
                <div class="rounded-lg border border-line bg-surface-muted/40 p-4">
                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                        <div class="lg:col-span-3">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Product') }}</label>
                            <select
                                class="w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
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
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Description') }}</label>
                            <input
                                type="text"
                                class="w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                :name="'items[' + index + '][description]'"
                                x-model="item.description"
                                required
                            >
                        </div>
                        <div class="lg:col-span-1">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Qty') }}</label>
                            <input type="number" step="0.01" min="0.01" class="w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :name="'items[' + index + '][quantity]'" x-model="item.quantity" required>
                        </div>
                        <div class="lg:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Unit Price') }}</label>
                            <input type="number" step="0.01" min="0" class="w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :name="'items[' + index + '][unit_price]'" x-model="item.unit_price" required>
                        </div>
                        <div class="lg:col-span-1">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Tax %') }}</label>
                            <input type="number" step="0.01" min="0" max="100" class="w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :name="'items[' + index + '][tax_rate]'" x-model="item.tax_rate">
                        </div>
                        <div class="lg:col-span-1">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Disc %') }}</label>
                            <input type="number" step="0.01" min="0" max="100" class="w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :name="'items[' + index + '][discount_percent]'" x-model="item.discount_percent">
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <button type="button" @click="removeItem(index)" class="text-xs text-danger hover:opacity-80" x-show="items.length > 1">{{ __('Remove') }}</button>
                        <p class="ms-auto text-sm text-ink-muted">{{ __('Line total') }}: <span class="font-semibold text-ink-heading" x-text="format(lineTotal(item))"></span></p>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-6 ms-auto max-w-sm rounded-lg border border-line bg-surface p-4">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-ink-muted">{{ __('Subtotal') }}</dt>
                    <dd class="font-medium text-ink-heading" x-text="format(subtotal)"></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-muted">{{ __('Discount') }}</dt>
                    <dd class="font-medium text-ink-heading" x-text="format(discountTotal)"></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-ink-muted">{{ __('Tax') }}</dt>
                    <dd class="font-medium text-ink-heading" x-text="format(taxTotal)"></dd>
                </div>
                <div class="flex justify-between border-t border-line pt-2">
                    <dt class="font-semibold text-ink-heading">{{ __('Total') }}</dt>
                    <dd class="font-bold text-ink-heading" x-text="format(grandTotal)"></dd>
                </div>
            </dl>
        </div>
    </div>
</div>
