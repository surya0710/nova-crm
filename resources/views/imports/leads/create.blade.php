<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('Import :leads', ['leads' => crm_term('leads')])"
        :subtitle="__('Upload a CSV or XLSX file to import leads')"
        max-width="2xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Imports'), 'href' => route('leads.import.create')],
                ['label' => crm_term('leads'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('leads.index')" variant="secondary" size="sm">
                {{ __('Back to :leads', ['leads' => crm_term('leads')]) }}
            </x-ui.button>
        </x-slot:actions>

        <div class="space-y-6">
            @include('imports._steps', ['current' => 'upload'])

            <x-forms.section
                :title="__('Download a template')"
                :subtitle="__('Templates include your column names, a sample row, and tenant lookup values.')"
            >
                <div class="sm:col-span-2 flex flex-wrap gap-3">
                    <x-ui.button :href="route('leads.import.template.xlsx')" variant="secondary" size="sm">
                        {{ __('Download Excel') }}
                    </x-ui.button>
                    <x-ui.button :href="route('leads.import.template.csv')" variant="secondary" size="sm">
                        {{ __('Download CSV') }}
                    </x-ui.button>
                </div>
            </x-forms.section>

            <form method="POST" action="{{ route('leads.import.store') }}" enctype="multipart/form-data" class="space-y-5">
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

                <div class="rounded-lg border border-line bg-surface-muted/50 px-4 py-3 text-sm text-ink">
                    <p class="font-medium text-ink-heading">{{ __('Tips') }}</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-ink-muted">
                        <li>{{ __('Include a header row with column names such as Email, Full Name, Phone, and Source.') }}</li>
                        <li>{{ __('Owner can be matched by member email or full name. Leave Owner blank to use Assignment rules.') }}</li>
                        <li>{{ __('Custom metadata fields for leads are imported when column headers match the field label or key.') }}</li>
                        <li>{{ __('Duplicate emails or phones are reported in preview and are not imported.') }}</li>
                    </ul>
                </div>

                <x-forms.footer
                    :cancel-href="route('leads.index')"
                    :submit-label="__('Upload & Preview')"
                />
            </form>
        </div>
    </x-layouts.create>
</x-app-layout>
