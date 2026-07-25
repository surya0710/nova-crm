<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $category->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="slug" :value="__('Slug')" />
        <x-text-input id="slug" class="block mt-1 w-full" type="text" name="slug" :value="old('slug', $category->slug)" placeholder="{{ __('auto-generated if empty') }}" />
        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" :value="__('Description')" />
        <textarea id="description" name="description" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $category->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="color" :value="__('Color')" />
        <x-text-input id="color" class="block mt-1 w-full" type="text" name="color" :value="old('color', $category->color)" placeholder="#4f46e5" />
        <x-input-error :messages="$errors->get('color')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="icon" :value="__('Icon')" />
        <x-text-input id="icon" class="block mt-1 w-full" type="text" name="icon" :value="old('icon', $category->icon)" placeholder="code" />
        <x-input-error :messages="$errors->get('icon')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="sort_order" :value="__('Sort Order')" />
        <x-text-input id="sort_order" class="block mt-1 w-full" type="number" name="sort_order" min="0" :value="old('sort_order', $category->sort_order)" />
        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
    </div>

    <div class="flex items-center gap-2 pt-6">
        <input type="hidden" name="is_active" value="0" />
        <input id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
        <x-input-label for="is_active" :value="__('Active')" class="!mt-0" />
        <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
    </div>
</div>
