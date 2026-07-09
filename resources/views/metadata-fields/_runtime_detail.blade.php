@if (($metadataFields ?? collect())->isNotEmpty())
    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-semibold text-slate-900">{{ __('Custom Fields') }}</h3>
            <p class="text-sm text-slate-500 mt-0.5">{{ __('Additional details configured for this record.') }}</p>
        </div>

        <dl class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
            @foreach ($metadataFields as $item)
                @php
                    $field = $item['field'];
                    $values = $record->custom_fields ?? [];
                    $value = is_array($values) && array_key_exists($field->key, $values)
                        ? $values[$field->key]
                        : null;
                    $displayValue = $field->is_sensitive
                        ? '********'
                        : $metadataPresenter->displayValue($field, $value);
                @endphp

                <div @class(['sm:col-span-2' => ($item['width'] ?? 'full') === 'full'])>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $field->label }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 whitespace-pre-wrap">{{ $displayValue }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
@endif
