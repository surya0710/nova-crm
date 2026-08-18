@php
    $defaultItem = [
        'product_id' => '',
        'sku' => '',
        'unit' => '',
        'hsn_sac' => '',
        'description' => '',
        'quantity' => 1,
        'unit_price' => 0,
        'tax_rate' => 0,
        'discount_percent' => 0,
        'cess_rate' => 0,
        'tax_inclusive' => false,
    ];
    $productOptions = $productOptions ?? collect();
    $customerTaxProfiles = $customerTaxProfiles ?? [];
    $taxConfig = $taxConfig ?? ['states' => [], 'seller_state_code' => null];
    $document = $document ?? null;
@endphp

<div
    x-data="{
        items: @js($initialItems),
        products: @js($productOptions),
        productQuery: '',
        customers: @js($customerTaxProfiles),
        taxConfig: @js($taxConfig),
        pricingMode: @js(old('pricing_mode', $document->pricing_mode ?? 'exclusive')),
        taxTreatment: @js(old('tax_treatment', $document->tax_treatment ?? 'standard')),
        placeOfSupply: @js(old('place_of_supply', $document->place_of_supply ?? '')),
        shippingAmount: @js((float) old('shipping_amount', $document->shipping_amount ?? 0)),
        addItem() {
            this.items.push({ product_id: '', sku: '', unit: '', hsn_sac: '', description: '', quantity: 1, unit_price: 0, tax_rate: 0, discount_percent: 0, cess_rate: 0, tax_inclusive: false });
        },
        removeItem(index) {
            if (this.items.length > 1) this.items.splice(index, 1);
        },
        moveItem(index, direction) {
            const target = index + direction;
            if (target < 0 || target >= this.items.length) return;
            const moved = this.items.splice(index, 1)[0];
            this.items.splice(target, 0, moved);
        },
        filteredProducts() {
            const q = (this.productQuery || '').toLowerCase();
            if (! q) return this.products;
            return this.products.filter(p => [p.name, p.sku, p.hsn_sac].filter(Boolean).some(v => String(v).toLowerCase().includes(q)));
        },
        selectProduct(index, productId) {
            const product = this.products.find(p => String(p.id) === String(productId));
            if (! product) return;
            this.items[index].product_id = product.id;
            this.items[index].description = product.description || product.name;
            this.items[index].sku = product.sku || '';
            this.items[index].unit = product.unit || '';
            this.items[index].hsn_sac = product.hsn_sac || '';
            this.items[index].unit_price = product.unit_price;
            this.items[index].tax_rate = product.tax_rate;
            this.items[index].discount_percent = product.default_discount_percent || 0;
            this.items[index].cess_rate = product.cess_rate || 0;
            this.items[index].tax_inclusive = !! product.tax_inclusive;
        },
        customerId() {
            const el = document.getElementById('customer_id');
            return el ? el.value : '';
        },
        get customerProfile() {
            return this.customers[this.customerId()] || null;
        },
        get effectivePlaceOfSupply() {
            return this.placeOfSupply || this.customerProfile?.place_of_supply || '';
        },
        get isExempt() {
            return this.taxTreatment === 'exempt' || this.taxTreatment === 'zero_rated' || !! this.customerProfile?.exempt;
        },
        get usesGst() {
            return !! this.taxConfig.seller_state_code && !! this.effectivePlaceOfSupply && ! this.isExempt;
        },
        get isIntraState() {
            return this.usesGst && this.taxConfig.seller_state_code === this.effectivePlaceOfSupply;
        },
        utgstFor(code) {
            const state = (this.taxConfig.states || []).find(s => String(s.code) === String(code));
            return !! state?.utgst;
        },
        lineSubtotal(item) {
            return (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
        },
        lineDiscount(item) {
            return this.lineSubtotal(item) * ((parseFloat(item.discount_percent) || 0) / 100);
        },
        lineNet(item) {
            return this.lineSubtotal(item) - this.lineDiscount(item);
        },
        lineInclusive(item) {
            return this.pricingMode === 'inclusive' || !! item.tax_inclusive;
        },
        lineTaxable(item) {
            const net = this.lineNet(item);
            if (this.isExempt) return net;
            const rate = (parseFloat(item.tax_rate) || 0) + (parseFloat(item.cess_rate) || 0);
            if (this.lineInclusive(item) && rate > 0) {
                return net / (1 + rate / 100);
            }
            return net;
        },
        lineGst(item) {
            if (this.isExempt) return 0;
            return this.lineTaxable(item) * ((parseFloat(item.tax_rate) || 0) / 100);
        },
        lineCess(item) {
            if (this.isExempt) return 0;
            return this.lineTaxable(item) * ((parseFloat(item.cess_rate) || 0) / 100);
        },
        lineTotal(item) {
            if (this.lineInclusive(item) && ! this.isExempt) return this.lineNet(item);
            return this.lineNet(item) + this.lineGst(item) + this.lineCess(item);
        },
        split(item) {
            const tax = this.lineGst(item);
            if (! this.usesGst) return { cgst: 0, sgst: 0, igst: 0, utgst: 0, other: tax };
            if (! this.isIntraState) return { cgst: 0, sgst: 0, igst: tax, utgst: 0, other: 0 };
            const half = tax / 2;
            if (this.utgstFor(this.effectivePlaceOfSupply)) return { cgst: half, sgst: 0, igst: 0, utgst: half, other: 0 };
            return { cgst: half, sgst: half, igst: 0, utgst: 0, other: 0 };
        },
        get subtotal() { return this.items.reduce((s, i) => s + this.lineSubtotal(i), 0); },
        get discountTotal() { return this.items.reduce((s, i) => s + this.lineDiscount(i), 0); },
        get taxableTotal() { return this.items.reduce((s, i) => s + this.lineTaxable(i), 0); },
        get cgstTotal() { return this.items.reduce((s, i) => s + this.split(i).cgst, 0); },
        get sgstTotal() { return this.items.reduce((s, i) => s + this.split(i).sgst, 0); },
        get igstTotal() { return this.items.reduce((s, i) => s + this.split(i).igst, 0); },
        get utgstTotal() { return this.items.reduce((s, i) => s + this.split(i).utgst, 0); },
        get otherTaxTotal() { return this.items.reduce((s, i) => s + this.split(i).other, 0); },
        get cessTotal() { return this.items.reduce((s, i) => s + this.lineCess(i), 0); },
        get taxTotal() { return this.cgstTotal + this.sgstTotal + this.igstTotal + this.utgstTotal + this.otherTaxTotal + this.cessTotal; },
        get grandTotal() {
            const shipping = parseFloat(this.shippingAmount) || 0;
            if (this.pricingMode === 'inclusive' && ! this.isExempt) return this.subtotal - this.discountTotal + shipping;
            return this.subtotal - this.discountTotal + this.taxTotal + shipping;
        },
        format(v) { return (parseFloat(v) || 0).toFixed(2); }
    }"
>
    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Pricing') }}</label>
            <select class="w-full rounded-md border-line text-sm shadow-sm" name="pricing_mode" x-model="pricingMode">
                @foreach (config('tax.pricing_modes') as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Tax treatment') }}</label>
            <select class="w-full rounded-md border-line text-sm shadow-sm" name="tax_treatment" x-model="taxTreatment">
                @foreach (config('tax.treatments') as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Place of supply') }}</label>
            <select class="w-full rounded-md border-line text-sm shadow-sm" name="place_of_supply" x-model="placeOfSupply">
                <option value="">{{ __('From customer') }}</option>
                @foreach (config('tax.states') as $code => $state)
                    <option value="{{ $code }}">{{ $code }} — {{ $state['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Shipping / other charges') }}</label>
            <input type="number" step="0.01" min="0" class="w-full rounded-md border-line text-sm shadow-sm" name="shipping_amount" x-model="shippingAmount">
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h4 class="text-sm font-semibold text-ink-heading">{{ __('Line Items') }}</h4>
        <div class="flex items-center gap-3">
            <input type="search" x-model="productQuery" placeholder="{{ __('Search products…') }}" class="w-56 rounded-md border-line text-sm shadow-sm">
            <button type="button" @click="addItem()" class="text-sm font-medium text-primary-600 hover:text-primary-700">+ {{ __('Add line') }}</button>
        </div>
    </div>

    <x-input-error :messages="$errors->get('items')" class="mb-3" />

    <div class="space-y-4">
        <template x-for="(item, index) in items" :key="index">
            <div class="rounded-lg border border-line bg-surface-muted/40 p-4">
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                    <div class="lg:col-span-3">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Product / service') }}</label>
                        <select class="w-full rounded-md border-line text-sm shadow-sm" x-model="item.product_id" @change="selectProduct(index, $event.target.value)">
                            <option value="">{{ __('Custom line') }}</option>
                            <template x-for="product in filteredProducts()" :key="product.id">
                                <option :value="product.id" x-text="product.sku ? (product.name + ' (' + product.sku + ')') : product.name"></option>
                            </template>
                        </select>
                        <input type="hidden" :name="'items[' + index + '][product_id]'" x-model="item.product_id">
                    </div>
                    <div class="lg:col-span-5">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Description') }}</label>
                        <input type="text" class="w-full rounded-md border-line text-sm shadow-sm" :name="'items[' + index + '][description]'" x-model="item.description" required>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('SKU') }}</label>
                        <input type="text" class="w-full rounded-md border-line text-sm shadow-sm" :name="'items[' + index + '][sku]'" x-model="item.sku">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('HSN / SAC') }}</label>
                        <input type="text" class="w-full rounded-md border-line text-sm shadow-sm" :name="'items[' + index + '][hsn_sac]'" x-model="item.hsn_sac">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Unit') }}</label>
                        <input type="text" class="w-full rounded-md border-line text-sm shadow-sm" :name="'items[' + index + '][unit]'" x-model="item.unit">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Qty') }}</label>
                        <input type="number" step="0.01" min="0.01" class="w-full rounded-md border-line text-sm shadow-sm" :name="'items[' + index + '][quantity]'" x-model="item.quantity" required>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Unit price') }}</label>
                        <input type="number" step="0.01" min="0" class="w-full rounded-md border-line text-sm shadow-sm" :name="'items[' + index + '][unit_price]'" x-model="item.unit_price" required>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Disc %') }}</label>
                        <input type="number" step="0.01" min="0" max="100" class="w-full rounded-md border-line text-sm shadow-sm" :name="'items[' + index + '][discount_percent]'" x-model="item.discount_percent">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Tax %') }}</label>
                        <input type="number" step="0.01" min="0" max="100" class="w-full rounded-md border-line text-sm shadow-sm" :name="'items[' + index + '][tax_rate]'" x-model="item.tax_rate">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Cess %') }}</label>
                        <input type="number" step="0.01" min="0" max="100" class="w-full rounded-md border-line text-sm shadow-sm" :name="'items[' + index + '][cess_rate]'" x-model="item.cess_rate">
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <button type="button" @click="moveItem(index, -1)" class="text-xs text-ink-muted hover:text-ink-heading" x-show="index > 0">{{ __('Move up') }}</button>
                        <button type="button" @click="moveItem(index, 1)" class="text-xs text-ink-muted hover:text-ink-heading" x-show="index < items.length - 1">{{ __('Move down') }}</button>
                        <button type="button" @click="removeItem(index)" class="text-xs text-danger" x-show="items.length > 1">{{ __('Remove') }}</button>
                    </div>
                    <p class="ms-auto text-sm text-ink-muted">{{ __('Line total') }}: <span class="font-semibold text-ink-heading" x-text="format(lineTotal(item))"></span></p>
                </div>
            </div>
        </template>
    </div>

    <div class="mt-6 ms-auto max-w-sm rounded-lg border border-line bg-surface p-4">
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-ink-muted">{{ __('Subtotal') }}</dt><dd class="font-medium text-ink-heading" x-text="format(subtotal)"></dd></div>
            <div class="flex justify-between"><dt class="text-ink-muted">{{ __('Discount') }}</dt><dd class="font-medium text-ink-heading" x-text="format(discountTotal)"></dd></div>
            <div class="flex justify-between"><dt class="text-ink-muted">{{ __('Taxable amount') }}</dt><dd class="font-medium text-ink-heading" x-text="format(taxableTotal)"></dd></div>
            <div class="flex justify-between" x-show="cgstTotal > 0"><dt class="text-ink-muted">{{ __('CGST') }}</dt><dd class="font-medium text-ink-heading" x-text="format(cgstTotal)"></dd></div>
            <div class="flex justify-between" x-show="sgstTotal > 0"><dt class="text-ink-muted">{{ __('SGST') }}</dt><dd class="font-medium text-ink-heading" x-text="format(sgstTotal)"></dd></div>
            <div class="flex justify-between" x-show="igstTotal > 0"><dt class="text-ink-muted">{{ __('IGST') }}</dt><dd class="font-medium text-ink-heading" x-text="format(igstTotal)"></dd></div>
            <div class="flex justify-between" x-show="utgstTotal > 0"><dt class="text-ink-muted">{{ __('UTGST') }}</dt><dd class="font-medium text-ink-heading" x-text="format(utgstTotal)"></dd></div>
            <div class="flex justify-between" x-show="otherTaxTotal > 0"><dt class="text-ink-muted">{{ __('Tax') }}</dt><dd class="font-medium text-ink-heading" x-text="format(otherTaxTotal)"></dd></div>
            <div class="flex justify-between" x-show="cessTotal > 0"><dt class="text-ink-muted">{{ __('Cess') }}</dt><dd class="font-medium text-ink-heading" x-text="format(cessTotal)"></dd></div>
            <div class="flex justify-between" x-show="(parseFloat(shippingAmount) || 0) > 0"><dt class="text-ink-muted">{{ __('Shipping / other') }}</dt><dd class="font-medium text-ink-heading" x-text="format(shippingAmount)"></dd></div>
            <div class="flex justify-between border-t border-line pt-2"><dt class="font-semibold text-ink-heading">{{ __('Grand total') }}</dt><dd class="font-bold text-ink-heading" x-text="format(grandTotal)"></dd></div>
        </dl>
    </div>
</div>
