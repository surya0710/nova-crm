{{-- Alpine line-item editor: keep structure intact for x-model / template bindings --}}
<div
    x-data="{
        items: @js($initialItems),
        products: @js($productOptions),
        addItem() { this.items.push({ product_id: '', description: '', quantity: 1, unit_price: 0, tax_rate: 0, discount_percent: 0 }); },
        removeItem(index) { if (this.items.length > 1) this.items.splice(index, 1); },
        selectProduct(index, productId) {
            const product = this.products.find(p => String(p.id) === String(productId));
            if (! product) return;
            this.items[index].product_id = product.id;
            this.items[index].description = product.name;
            this.items[index].unit_price = product.unit_price;
            this.items[index].tax_rate = product.tax_rate;
        },
        lineSubtotal(item) { return (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0); },
        lineDiscount(item) { return this.lineSubtotal(item) * ((parseFloat(item.discount_percent) || 0) / 100); },
        lineTax(item) { const t = this.lineSubtotal(item) - this.lineDiscount(item); return t * ((parseFloat(item.tax_rate) || 0) / 100); },
        lineTotal(item) { return this.lineSubtotal(item) - this.lineDiscount(item) + this.lineTax(item); },
        get subtotal() { return this.items.reduce((s, i) => s + this.lineSubtotal(i), 0); },
        get discountTotal() { return this.items.reduce((s, i) => s + this.lineDiscount(i), 0); },
        get taxTotal() { return this.items.reduce((s, i) => s + this.lineTax(i), 0); },
        get grandTotal() { return this.subtotal - this.discountTotal + this.taxTotal; },
        format(v) { return (parseFloat(v) || 0).toFixed(2); }
    }"
>
    <div class="mb-4 flex items-center justify-between">
        <h4 class="text-sm font-semibold text-ink-heading">{{ __('Line Items') }}</h4>
        <button type="button" @click="addItem()" class="text-sm font-medium text-primary-600 hover:text-primary-700">+ {{ __('Add line') }}</button>
    </div>
    <x-input-error :messages="$errors->get('items')" class="mb-3" />
    <div class="space-y-4">
        <template x-for="(item, index) in items" :key="index">
            <div class="rounded-lg border border-line bg-surface-muted/40 p-4">
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                    <div class="lg:col-span-3">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ crm_term('product') }}</label>
                        <select class="w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" x-model="item.product_id" @change="selectProduct(index, $event.target.value)">
                            <option value="">{{ __('Custom line') }}</option>
                            <template x-for="product in products" :key="product.id"><option :value="product.id" x-text="product.name"></option></template>
                        </select>
                        <input type="hidden" :name="'items[' + index + '][product_id]'" x-model="item.product_id">
                    </div>
                    <div class="lg:col-span-4">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Description') }}</label>
                        <input type="text" class="w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :name="'items[' + index + '][description]'" x-model="item.description" required>
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
                    <button type="button" @click="removeItem(index)" class="text-xs text-danger" x-show="items.length > 1">{{ __('Remove') }}</button>
                    <p class="ms-auto text-sm text-ink-muted">{{ __('Line total') }}: <span class="font-semibold text-ink-heading" x-text="format(lineTotal(item))"></span></p>
                </div>
            </div>
        </template>
    </div>
    <div class="mt-6 ms-auto max-w-sm rounded-lg border border-line bg-surface p-4">
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-ink-muted">{{ __('Subtotal') }}</dt><dd class="font-medium text-ink-heading" x-text="format(subtotal)"></dd></div>
            <div class="flex justify-between"><dt class="text-ink-muted">{{ __('Discount') }}</dt><dd class="font-medium text-ink-heading" x-text="format(discountTotal)"></dd></div>
            <div class="flex justify-between"><dt class="text-ink-muted">{{ __('Tax') }}</dt><dd class="font-medium text-ink-heading" x-text="format(taxTotal)"></dd></div>
            <div class="flex justify-between border-t border-line pt-2"><dt class="font-semibold text-ink-heading">{{ __('Total') }}</dt><dd class="font-bold text-ink-heading" x-text="format(grandTotal)"></dd></div>
        </dl>
    </div>
</div>
