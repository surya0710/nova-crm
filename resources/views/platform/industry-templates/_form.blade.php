@php
    $payload = old('draft_payload', json_encode($template->draft_payload ?: app(\App\Services\Platform\IndustryTemplatePayloadValidator::class)->defaultPayload(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
@endphp

@if ($errors->any())
    <div class="mb-4 rounded-lg bg-rose-900/40 border border-rose-700 text-rose-200 px-4 py-3 text-sm">
        <div class="font-medium mb-1">{{ __('Please fix the template details.') }}</div>
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4">
        <h2 class="font-medium text-white">{{ __('Template Details') }}</h2>

        <div>
            <label for="name" class="block text-sm text-slate-300 mb-1">{{ __('Name') }}</label>
            <input id="name" name="name" value="{{ old('name', $template->name) }}" required
                class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm" />
        </div>

        <div>
            <label for="slug" class="block text-sm text-slate-300 mb-1">{{ __('Slug') }}</label>
            <input id="slug" name="slug" value="{{ old('slug', $template->slug) }}"
                class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm" />
            <p class="mt-1 text-xs text-slate-500">{{ __('Leave blank to generate from the name.') }}</p>
        </div>

        <div>
            <label for="industry" class="block text-sm text-slate-300 mb-1">{{ __('Industry Key') }}</label>
            <input id="industry" name="industry" value="{{ old('industry', $template->industry) }}"
                class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm" />
        </div>

        <div>
            <label for="description" class="block text-sm text-slate-300 mb-1">{{ __('Description') }}</label>
            <textarea id="description" name="description" rows="3"
                class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm">{{ old('description', $template->description) }}</textarea>
        </div>
    </div>

    <div class="rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4">
        <h2 class="font-medium text-white">{{ __('Administration') }}</h2>

        <div>
            <label for="visibility" class="block text-sm text-slate-300 mb-1">{{ __('Visibility') }}</label>
            <select id="visibility" name="visibility" class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm">
                @foreach ($visibilities as $value => $label)
                    <option value="{{ $value }}" @selected(old('visibility', $template->visibility ?: 'internal') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="sort_order" class="block text-sm text-slate-300 mb-1">{{ __('Sort Order') }}</label>
            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $template->sort_order ?? 0) }}"
                class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm" />
        </div>

        <div class="rounded-lg bg-slate-950 border border-slate-800 p-3 text-xs text-slate-400">
            {{ __('Published versions are immutable. Editing this form changes the draft payload only; publishing creates a new version snapshot.') }}
        </div>
    </div>
</div>

<div class="mt-6 rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-3">
    <div class="flex items-center justify-between">
        <h2 class="font-medium text-white">{{ __('Canonical Payload Draft') }}</h2>
        <span class="text-xs text-slate-500">{{ __('Schema v:version', ['version' => config('industry_templates.schema_version')]) }}</span>
    </div>
    <textarea name="draft_payload" rows="28" spellcheck="false"
        class="w-full font-mono rounded-lg bg-slate-950 border-slate-700 text-slate-100 text-xs">{{ $payload }}</textarea>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('platform.industry-templates.index') }}" class="text-sm text-slate-400 hover:text-white">{{ __('Cancel') }}</a>
    <button type="submit" class="rounded-lg bg-violet-600 hover:bg-violet-500 px-4 py-2 text-sm font-medium">{{ __('Save Draft') }}</button>
</div>
