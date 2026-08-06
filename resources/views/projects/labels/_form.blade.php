<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $label->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="color" :value="__('Color')" />
        <div class="mt-1 flex items-center gap-3">
            <input
                id="color"
                type="color"
                name="color"
                value="{{ old('color', $label->color ?: '#64748b') }}"
                class="h-10 w-14 rounded border border-slate-300 bg-white p-1 cursor-pointer"
            />
            <span class="text-sm text-slate-500">{{ old('color', $label->color ?: '#64748b') }}</span>
        </div>
        <x-input-error :messages="$errors->get('color')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" :value="__('Description')" />
        <textarea id="description" name="description" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $label->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>
</div>
