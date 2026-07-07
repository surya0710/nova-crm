<div class="space-y-8">
    <div>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Product Details') }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="sm:col-span-2">
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $product->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="sku" :value="__('SKU')" />
                <x-text-input id="sku" class="block mt-1 w-full" type="text" name="sku" :value="old('sku', $product->sku)" placeholder="SKU-001" />
                <x-input-error :messages="$errors->get('sku')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="category" :value="__('Category')" />
                <x-text-input id="category" class="block mt-1 w-full" type="text" name="category" :value="old('category', $product->category)" placeholder="Software, Consulting…" />
                <x-input-error :messages="$errors->get('category')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="description" :value="__('Description')" />
                <textarea id="description" name="description" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $product->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Pricing & Tax') }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <x-input-label for="type" :value="__('Type')" />
                <select id="type" name="type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                    @foreach (config('products.types') as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $product->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('type')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="unit" :value="__('Unit')" />
                <select id="unit" name="unit" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach (config('products.units') as $value => $label)
                        <option value="{{ $value }}" @selected(old('unit', $product->unit) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('unit')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="unit_price" :value="__('Unit Price')" />
                <x-text-input id="unit_price" class="block mt-1 w-full" type="number" name="unit_price" step="0.01" min="0" :value="old('unit_price', $product->unit_price)" required />
                <x-input-error :messages="$errors->get('unit_price')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="currency" :value="__('Currency')" />
                <select id="currency" name="currency" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                    @foreach (config('products.currencies') as $value => $label)
                        <option value="{{ $value }}" @selected(old('currency', $product->currency) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="tax_rate" :value="__('Tax Rate (%)')" />
                <x-text-input id="tax_rate" class="block mt-1 w-full" type="number" name="tax_rate" step="0.01" min="0" max="100" :value="old('tax_rate', $product->tax_rate)" />
                <x-input-error :messages="$errors->get('tax_rate')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="status" :value="__('Status')" />
                <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                    @foreach (config('products.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $product->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>
        </div>
    </div>
</div>
