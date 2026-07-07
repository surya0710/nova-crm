@props(['name' => 'logo', 'currentUrl' => null, 'removable' => false])

<div
    x-data="{
        preview: @js($currentUrl),
        showRemove: @js($removable && $currentUrl),
        onFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.preview = URL.createObjectURL(file);
            this.showRemove = false;
        },
        clearPreview() {
            this.preview = null;
            this.showRemove = false;
            this.$refs.fileInput.value = '';
            if (this.$refs.removeInput) this.$refs.removeInput.checked = true;
        }
    }"
    class="space-y-3"
>
    <div class="flex items-center gap-4">
        <template x-if="preview">
            <img :src="preview" alt="Logo preview" class="h-20 w-20 rounded-xl object-cover ring-2 ring-slate-200" />
        </template>
        <template x-if="!preview">
            <div class="h-20 w-20 rounded-xl bg-slate-100 flex items-center justify-center ring-2 ring-slate-200">
                <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        </template>

        <div class="flex-1">
            <input
                x-ref="fileInput"
                type="file"
                name="{{ $name }}"
                accept="image/jpeg,image/png,image/jpg,image/webp"
                class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                @change="onFileChange($event)"
            />
            <p class="mt-1 text-xs text-slate-500">{{ __('PNG, JPG or WebP. Max 2 MB.') }}</p>
            @if ($removable && $currentUrl)
                <button
                    type="button"
                    x-show="preview"
                    @click="clearPreview()"
                    class="mt-2 text-xs text-red-600 hover:text-red-800 font-medium"
                >
                    {{ __('Remove logo') }}
                </button>
                <input type="checkbox" name="remove_logo" value="1" class="hidden" x-ref="removeInput">
            @endif
        </div>
    </div>

    <x-input-error :messages="$errors->get($name)" />
</div>
