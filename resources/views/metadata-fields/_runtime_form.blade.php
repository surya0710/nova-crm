@if (($metadataFields ?? collect())->isNotEmpty())
    <div class="space-y-8 pt-6 border-t border-slate-100">
        @if ($showMetadataHeader ?? true)
            <div>
                <h4 class="text-sm font-semibold text-slate-900">{{ __('Custom Fields') }}</h4>
                <p class="mt-1 text-xs text-slate-500">{{ __('Additional fields configured for your organization.') }}</p>
            </div>
        @endif

        @foreach ($metadataFields->groupBy('group_key') as $groupKey => $fields)
            @php
                $group = $fields->first()['group'] ?? null;
                $groupLabel = $group?->label ?? __('Additional Details');
            @endphp

            <div>
                <h5 class="text-sm font-semibold text-slate-900 mb-4">{{ $groupLabel }}</h5>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    @foreach ($fields as $item)
                        @php
                            $field = $item['field'];
                            $value = $metadataPresenter->formValue($field, $record ?? null, old('custom_fields'));
                            $inputId = $metadataPresenter->inputId($field);
                            $inputName = $metadataPresenter->inputName($field);
                            $inputClass = 'block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm';
                            $scalarValue = is_scalar($value) ? $value : '';
                            $selectedValues = collect((array) $value)->map(fn ($selectedValue) => (string) $selectedValue)->all();
                        @endphp

                        <div @class(['sm:col-span-2' => ($item['width'] ?? 'full') === 'full'])>
                            <x-input-label :for="$inputId">
                                {{ $field->label }}
                                @if ($field->is_required)
                                    <span class="text-red-500">*</span>
                                @endif
                            </x-input-label>

                            @switch($field->type)
                                @case('textarea')
                                    <textarea id="{{ $inputId }}" name="{{ $inputName }}" rows="3" class="{{ $inputClass }}">{{ $scalarValue }}</textarea>
                                    @break

                                @case('boolean')
                                    <label class="mt-1 flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                        <input type="hidden" name="{{ $inputName }}" value="0">
                                        <input type="checkbox" name="{{ $inputName }}" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked((bool) $value)>
                                        <span>{{ __('Yes') }}</span>
                                    </label>
                                    @break

                                @case('select')
                                    <select id="{{ $inputId }}" name="{{ $inputName }}" class="{{ $inputClass }}">
                                        <option value="">{{ __('Select...') }}</option>
                                        @foreach ($field->options as $option)
                                            <option value="{{ $option->value }}" @selected((string) $scalarValue === (string) $option->value)>{{ $option->label }}</option>
                                        @endforeach
                                    </select>
                                    @break

                                @case('radio')
                                    <input type="hidden" name="{{ $inputName }}" value="">
                                    <div class="mt-2 space-y-2">
                                        @foreach ($field->options as $option)
                                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                                <input type="radio" name="{{ $inputName }}" value="{{ $option->value }}" class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked((string) $scalarValue === (string) $option->value)>
                                                <span>{{ $option->label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @break

                                @case('multi_select')
                                    <input type="hidden" name="{{ $inputName }}" value="">
                                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach ($field->options as $option)
                                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                                <input type="checkbox" name="{{ $inputName }}[]" value="{{ $option->value }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(in_array((string) $option->value, $selectedValues, true))>
                                                <span>{{ $option->label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @break

                                @case('number')
                                    <x-text-input id="{{ $inputId }}" class="block mt-1 w-full" type="number" name="{{ $inputName }}" :value="$scalarValue" />
                                    @break

                                @case('decimal')
                                @case('currency')
                                @case('percentage')
                                    <x-text-input id="{{ $inputId }}" class="block mt-1 w-full" type="number" step="0.01" name="{{ $inputName }}" :value="$scalarValue" />
                                    @break

                                @case('date')
                                    <x-text-input id="{{ $inputId }}" class="block mt-1 w-full" type="date" name="{{ $inputName }}" :value="$scalarValue" />
                                    @break

                                @case('datetime')
                                    <x-text-input id="{{ $inputId }}" class="block mt-1 w-full" type="datetime-local" name="{{ $inputName }}" :value="$scalarValue" />
                                    @break

                                @case('time')
                                    <x-text-input id="{{ $inputId }}" class="block mt-1 w-full" type="time" name="{{ $inputName }}" :value="$scalarValue" />
                                    @break

                                @case('email')
                                    <x-text-input id="{{ $inputId }}" class="block mt-1 w-full" type="email" name="{{ $inputName }}" :value="$scalarValue" />
                                    @break

                                @case('url')
                                    <x-text-input id="{{ $inputId }}" class="block mt-1 w-full" type="url" name="{{ $inputName }}" :value="$scalarValue" />
                                    @break

                                @default
                                    <x-text-input id="{{ $inputId }}" class="block mt-1 w-full" type="text" name="{{ $inputName }}" :value="$scalarValue" />
                            @endswitch

                            @if ($field->description)
                                <p class="mt-1 text-xs text-slate-500">{{ $field->description }}</p>
                            @endif

                            <x-input-error :messages="$errors->get($metadataPresenter->errorKey($field))" class="mt-2" />
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
