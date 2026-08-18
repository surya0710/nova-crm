<div class="space-y-8">
    <x-forms.section :title="__('Category')">
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Name')" name="name" required>
                <x-forms.input id="name" type="text" name="name" :value="old('name', $category->name)" required />
            </x-forms.field>
        </div>
        <x-forms.field :label="__('Slug')" name="slug">
            <x-forms.input id="slug" type="text" name="slug" :value="old('slug', $category->slug)" />
        </x-forms.field>
        <x-forms.field :label="__('Sort order')" name="sort_order">
            <x-forms.input id="sort_order" type="number" name="sort_order" min="0" :value="old('sort_order', $category->sort_order)" />
        </x-forms.field>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Description')" name="description">
                <x-forms.textarea id="description" name="description" rows="3">{{ old('description', $category->description) }}</x-forms.textarea>
            </x-forms.field>
        </div>
        <div class="sm:col-span-2">
            <label class="inline-flex items-center gap-2 text-sm text-ink">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active)) class="rounded border-line text-primary-600 focus:ring-primary-500">
                {{ __('Active') }}
            </label>
        </div>
    </x-forms.section>
</div>
