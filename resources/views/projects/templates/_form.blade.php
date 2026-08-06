<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $template->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="slug" :value="__('Slug')" />
        <x-text-input id="slug" class="block mt-1 w-full" type="text" name="slug" :value="old('slug', $template->slug)" placeholder="{{ __('auto-generated if empty') }}" />
        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="category" :value="__('Category')" />
        <x-text-input id="category" class="block mt-1 w-full" type="text" name="category" :value="old('category', $template->category)" />
        <x-input-error :messages="$errors->get('category')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="industry" :value="__('Industry')" />
        <x-text-input id="industry" class="block mt-1 w-full" type="text" name="industry" :value="old('industry', $template->industry)" />
        <x-input-error :messages="$errors->get('industry')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="department_id" :value="__('Department ID')" />
        <x-text-input id="department_id" class="block mt-1 w-full" type="number" name="department_id" :value="old('department_id', $template->department_id)" min="1" />
        <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" :value="__('Description')" />
        <textarea id="description" name="description" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $template->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="flex items-center gap-2">
        <input type="hidden" name="is_favorite" value="0" />
        <input id="is_favorite" type="checkbox" name="is_favorite" value="1" @checked(old('is_favorite', $template->is_favorite)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
        <x-input-label for="is_favorite" :value="__('Favorite')" class="!mt-0" />
    </div>
</div>
