<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Import :customers', ['customers' => crm_term('customers')]) }}</h1>
                <p class="text-sm text-slate-500">{{ __('Upload a CSV or XLSX file to import customers') }}</p>
            </div>
            <a href="{{ route('customers.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                {{ __('Back to :customers', ['customers' => crm_term('customers')]) }}
            </a>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="max-w-2xl">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('customers.import.store') }}" enctype="multipart/form-data" class="space-y-5">
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
                        <li>{{ __('Include a header row with column names such as Email, Full Name, Phone, and Status.') }}</li>
                        <li>{{ __('Owner can be matched by member email or full name.') }}</li>
                        <li>{{ __('Custom metadata fields for customers are imported when column headers match the field label or key.') }}</li>
                        <li>{{ __('Duplicate emails or phones are reported in preview and are not imported.') }}</li>
                    </ul>
                </div>

                <div class="flex items-center gap-3">
                    <x-primary-button>{{ __('Upload & Preview') }}</x-primary-button>
                    <a href="{{ route('customers.index') }}" class="text-sm text-slate-600 hover:text-slate-800">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
