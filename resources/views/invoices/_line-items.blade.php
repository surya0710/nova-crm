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
    <div class="flex items-center justify-between mb-4">
        <h4 class="text-sm font-semibold text-slate-900">{{ __('Line Items') }}</h4>
        <button type="button" @click="addItem()" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">+ {{ __('Add line') }}</button>
    </div>
    <x-input-error :messages="$errors->get('items')" class="mb-3" />
    <div class="space-y-4">
        <template x-for="(item, index) in items" :key="index">
            <div class="rounded-lg border border-slate-200 p-4 bg-slate-50/50">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ crm_term('product') }}</label>
                        <select class="w-full text-sm border-gray-300 rounded-md shadow-sm" x-model="item.product_id" @change="selectProduct(index, $event.target.value)">
                            <option value="">{{ __('Custom line') }}</option>
                            <template x-for="product in products" :key="product.id"><option :value="product.id" x-text="product.name"></option></template>
                        </select>
                        <input type="hidden" :name="'items[' + index + '][product_id]'" x-model="item.product_id">
                    </div>
                    <div class="lg:col-span-4">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ __('Description') }}</label>
                        <input type="text" class="w-full text-sm border-gray-300 rounded-md shadow-sm" :name="'items[' + index + '][description]'" x-model="item.description" required>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ __('Qty') }}</label>
                        <input type="number" step="0.01" min="0.01" class="w-full text-sm border-gray-300 rounded-md shadow-sm" :name="'items[' + index + '][quantity]'" x-model="item.quantity" required>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ __('Unit Price') }}</label>
                        <input type="number" step="0.01" min="0" class="w-full text-sm border-gray-300 rounded-md shadow-sm" :name="'items[' + index + '][unit_price]'" x-model="item.unit_price" required>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ __('Tax %') }}</label>
                        <input type="number" step="0.01" min="0" max="100" class="w-full text-sm border-gray-300 rounded-md shadow-sm" :name="'items[' + index + '][tax_rate]'" x-model="item.tax_rate">
                    </div>
                    <div class="lg:col-span-1">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ __('Disc %') }}</label>
                        <input type="number" step="0.01" min="0" max="100" class="w-full text-sm border-gray-300 rounded-md shadow-sm" :name="'items[' + index + '][discount_percent]'" x-model="item.discount_percent">
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <button type="button" @click="removeItem(index)" class="text-xs text-red-600" x-show="items.length > 1">{{ __('Remove') }}</button>
                    <p class="text-sm text-slate-600 ml-auto">{{ __('Line total') }}: <span class="font-semibold" x-text="format(lineTotal(item))"></span></p>
                </div>
            </div>
        </template>
    </div>
    <div class="mt-6 rounded-lg border border-slate-200 bg-white p-4 max-w-sm ml-auto">
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('Subtotal') }}</dt><dd class="font-medium" x-text="format(subtotal)"></dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('Discount') }}</dt><dd class="font-medium" x-text="format(discountTotal)"></dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('Tax') }}</dt><dd class="font-medium" x-text="format(taxTotal)"></dd></div>
            <div class="flex justify-between border-t pt-2"><dt class="font-semibold">{{ __('Total') }}</dt><dd class="font-bold" x-text="format(grandTotal)"></dd></div>
        </dl>
    </div>
</div>
