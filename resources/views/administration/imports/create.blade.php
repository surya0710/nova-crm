<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('Import :entity', ['entity' => $entityLabel])"
        :subtitle="__('Upload a CSV or XLSX file using the standard Import Center workflow')"
        max-width="2xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Import Center'), 'href' => route('administration.imports.index')],
                ['label' => $entityLabel, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('administration.imports.index')" variant="secondary" size="sm">
                {{ __('Back to Import Center') }}
            </x-ui.button>
        </x-slot:actions>

        <div class="space-y-6">
            @include('imports._steps', ['current' => 'upload'])

            <x-forms.section
                :title="__('Download a template')"
                :subtitle="__('Templates include required columns, a sample row, and validation notes.')"
            >
                <div class="sm:col-span-2 flex flex-wrap gap-3">
                    <x-ui.button :href="route('administration.imports.template', [$entityType, 'xlsx'])" variant="secondary" size="sm">
                        {{ __('Download Excel') }}
                    </x-ui.button>
                    <x-ui.button :href="route('administration.imports.template', [$entityType, 'csv'])" variant="secondary" size="sm">
                        {{ __('Download CSV') }}
                    </x-ui.button>
                </div>
            </x-forms.section>

            <form method="POST" action="{{ route('administration.imports.store', $entityType) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <x-forms.field
                    :label="__('Spreadsheet file')"
                    name="file"
                    required
                    :hint="__('Supported formats: CSV and XLSX. Maximum size: :kb KB.', ['kb' => config('import.max_upload_kilobytes', 10240)])"
                >
                    <input
                        id="file"
                        name="file"
                        type="file"
                        accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        class="block w-full text-sm text-ink file:mr-4 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100"
                        required
                    />
                </x-forms.field>

                <x-forms.field
                    :label="__('Duplicate strategy')"
                    name="duplicate_strategy"
                    :hint="__('Skip existing records, update them, or always create new rows.')"
                >
                    <select id="duplicate_strategy" name="duplicate_strategy" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="skip" @selected(old('duplicate_strategy', 'skip') === 'skip')>{{ __('Skip duplicates') }}</option>
                        <option value="update" @selected(old('duplicate_strategy') === 'update')>{{ __('Update existing') }}</option>
                        <option value="create" @selected(old('duplicate_strategy') === 'create')>{{ __('Always create new') }}</option>
                    </select>
                </x-forms.field>

                <x-entity.section :title="__('Fields')">
                    <ul class="grid gap-2 sm:grid-cols-2 text-sm text-ink-muted">
                        @foreach ($fields as $field)
                            <li>
                                <span class="font-medium text-ink-heading">{{ $field->label }}</span>
                                @if ($field->required)
                                    <span class="text-danger">*</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </x-entity.section>

                <x-forms.footer
                    :cancel-href="route('administration.imports.index')"
                    :submit-label="__('Upload & Preview')"
                />
            </form>
        </div>
    </x-layouts.create>
</x-app-layout>
