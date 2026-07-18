<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Import :leads', ['leads' => crm_term('leads')]) }}</h1>
                <p class="text-sm text-slate-500">{{ __('Upload a CSV or XLSX file to import leads') }}</p>
            </div>
            <a href="{{ route('leads.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                {{ __('Back to :leads', ['leads' => crm_term('leads')]) }}
            </a>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="max-w-2xl space-y-5">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Download a template') }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Download a template before importing your data. Templates include your column names, a sample row, and tenant lookup values.') }}
            </p>
            <div class="mt-4 flex flex-wrap gap-3">
                <a
                    href="{{ route('leads.import.template.xlsx') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
                >
                    {{ __('Download Excel') }}
                </a>
                <a
                    href="{{ route('leads.import.template.csv') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
                >
                    {{ __('Download CSV') }}
                </a>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('leads.import.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="file" :value="__('Spreadsheet file')" />
                    <input
                        id="file"
                        name="file"
                        type="file"
                        accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        class="mt-1 block w-full text-sm text-slate-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                        required
                    />
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    <p class="mt-2 text-xs text-slate-500">
                        {{ __('Supported formats: CSV and XLSX. Maximum size: :kb KB.', ['kb' => config('import.max_upload_kilobytes', 10240)]) }}
                    </p>
                </div>

                <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <p class="font-medium text-slate-800">{{ __('Tips') }}</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li>{{ __('Include a header row with column names such as Email, Full Name, Phone, and Source.') }}</li>
                        <li>{{ __('Owner can be matched by member email or full name. Leave Owner blank to use Assignment rules.') }}</li>
                        <li>{{ __('Custom metadata fields for leads are imported when column headers match the field label or key.') }}</li>
                        <li>{{ __('Duplicate emails or phones are reported in preview and are not imported.') }}</li>
                    </ul>
                </div>

                <div class="flex items-center gap-3">
                    <x-primary-button>{{ __('Upload & Preview') }}</x-primary-button>
                    <a href="{{ route('leads.index') }}" class="text-sm text-slate-600 hover:text-slate-800">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
