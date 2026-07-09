@php
    $isLocked = $field->exists && ! $field->isDraft();
    $optionTypes = config('metadata.option_field_types');
    $booleanFlags = [
        'is_required' => __('Required'),
        'is_unique' => __('Unique'),
        'is_searchable' => __('Searchable'),
        'is_filterable' => __('Filterable'),
        'is_sortable' => __('Sortable'),
        'is_reportable' => __('Reportable'),
        'is_exportable' => __('Exportable'),
        'is_api_visible' => __('API Visible'),
        'is_sensitive' => __('Sensitive / PII'),
    ];
@endphp

<div
    x-data="{ type: @js(old('type', $field->type ?? 'text')), optionTypes: @js($optionTypes) }"
    class="space-y-8"
>
    <div>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Field Identity') }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <x-input-label for="entity_type" :value="__('Entity')" />
                @if ($isLocked)
                    <input type="hidden" name="entity_type" value="{{ $field->entity_type }}">
                @endif
                <select id="entity_type" name="entity_type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" @disabled($isLocked) required>
                    @foreach (config('metadata.entities') as $value => $label)
                        <option value="{{ $value }}" @selected(old('entity_type', $field->entity_type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('entity_type')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="type" :value="__('Field Type')" />
                @if ($isLocked)
                    <input type="hidden" name="type" value="{{ $field->type }}">
                @endif
                <select id="type" name="type" x-model="type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" @disabled($isLocked) required>
                    @foreach (config('metadata.field_types') as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $field->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('type')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="label" :value="__('Label')" />
                <x-text-input id="label" class="block mt-1 w-full" type="text" name="label" :value="old('label', $field->label)" required />
                <x-input-error :messages="$errors->get('label')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="key" :value="__('Stable Key')" />
                @if ($isLocked)
                    <input type="hidden" name="key" value="{{ $field->key }}">
                @endif
                <x-text-input id="key" class="block mt-1 w-full font-mono text-sm" type="text" name="key" :value="old('key', $field->key)" placeholder="visa_type" :disabled="$isLocked" />
                <p class="mt-1 text-xs text-slate-500">{{ __('Keys are locked after publish because APIs, imports, reports, and automations depend on them.') }}</p>
                <x-input-error :messages="$errors->get('key')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="group_label" :value="__('Group')" />
                <x-text-input id="group_label" class="block mt-1 w-full" type="text" name="group_label" :value="old('group_label', $field->group?->label)" placeholder="Immigration Details" />
                <x-input-error :messages="$errors->get('group_label')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="sort_order" :value="__('Sort Order')" />
                <x-text-input id="sort_order" class="block mt-1 w-full" type="number" name="sort_order" min="0" :value="old('sort_order', $field->sort_order ?? 0)" />
                <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="description" :value="__('Help Text / Description')" />
                <textarea id="description" name="description" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $field->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Capabilities') }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach ($booleanFlags as $name => $label)
                <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                    <input type="hidden" name="{{ $name }}" value="0">
                    <input type="checkbox" name="{{ $name }}" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old($name, $field->{$name} ?? in_array($name, ['is_exportable', 'is_api_visible'], true)))>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div x-show="optionTypes.includes(type)" x-cloak>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Options') }}</h4>
        <x-input-label for="options_text" :value="__('One option per line')" />
        <textarea id="options_text" name="options_text" rows="6" class="block mt-1 w-full font-mono text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="student_visa|Student Visa&#10;work_visa|Work Visa">{{ old('options_text', $optionsText) }}</textarea>
        <p class="mt-1 text-xs text-slate-500">{{ __('Use value|Label. Values remain stable while labels may change later.') }}</p>
        <x-input-error :messages="$errors->get('options_text')" class="mt-2" />
        <x-input-error :messages="$errors->get('options')" class="mt-2" />
    </div>

    <div>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Advanced Metadata') }}</h4>
        <div class="grid grid-cols-1 gap-5">
            @foreach ([
                'validation_rules_json' => ['Validation Rules', $field->validation_rules],
                'visibility_rules_json' => ['Visibility Rules', $field->visibility_rules],
                'display_rules_json' => ['Display Rules', $field->display_rules],
                'permission_rules_json' => ['Permission Rules', $field->permission_rules],
            ] as $name => [$label, $value])
                <div>
                    <x-input-label :for="$name" :value="__($label)" />
                    <textarea id="{{ $name }}" name="{{ $name }}" rows="3" class="block mt-1 w-full font-mono text-xs border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old($name, $value ? json_encode($value, JSON_PRETTY_PRINT) : '') }}</textarea>
                    <x-input-error :messages="$errors->get($name)" class="mt-2" />
                </div>
            @endforeach
        </div>
    </div>
</div>
