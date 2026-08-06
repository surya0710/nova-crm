<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $stage->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="sequence" :value="__('Sequence')" />
        <x-text-input id="sequence" class="block mt-1 w-full" type="number" name="sequence" min="0" :value="old('sequence', $stage->sequence)" />
        <x-input-error :messages="$errors->get('sequence')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" :value="__('Description')" />
        <textarea id="description" name="description" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $stage->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="color" :value="__('Color')" />
        <x-text-input id="color" class="block mt-1 w-full" type="text" name="color" :value="old('color', $stage->color)" placeholder="#4f46e5" />
        <x-input-error :messages="$errors->get('color')" class="mt-2" />
    </div>

    <div class="flex items-center gap-2 pt-6">
        <input type="hidden" name="is_default" value="0" />
        <input id="is_default" type="checkbox" name="is_default" value="1" @checked(old('is_default', $stage->is_default)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
        <x-input-label for="is_default" :value="__('Default Stage')" class="!mt-0" />
        <x-input-error :messages="$errors->get('is_default')" class="mt-2" />
    </div>
</div>
