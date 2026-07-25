<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $status->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="color" :value="__('Color')" />
        <x-text-input id="color" class="block mt-1 w-full" type="text" name="color" :value="old('color', $status->color)" placeholder="#22c55e" />
        <x-input-error :messages="$errors->get('color')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="sort_order" :value="__('Sort Order')" />
        <x-text-input id="sort_order" class="block mt-1 w-full" type="number" name="sort_order" min="0" :value="old('sort_order', $status->sort_order)" />
        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
    </div>

    <div class="space-y-3 pt-2">
        <div class="flex items-center gap-2">
            <input type="hidden" name="is_default" value="0" />
            <input id="is_default" type="checkbox" name="is_default" value="1" @checked(old('is_default', $status->is_default)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
            <x-input-label for="is_default" :value="__('Default Status')" class="!mt-0" />
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="is_closed" value="0" />
            <input id="is_closed" type="checkbox" name="is_closed" value="1" @checked(old('is_closed', $status->is_closed)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
            <x-input-label for="is_closed" :value="__('Closed Status')" class="!mt-0" />
        </div>
        <x-input-error :messages="$errors->get('is_default')" class="mt-2" />
        <x-input-error :messages="$errors->get('is_closed')" class="mt-2" />
    </div>
</div>
