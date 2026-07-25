<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('Upload Document')"
        :subtitle="$employee->full_name"
        max-width="2xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Employees'), 'href' => route('hrms.employees.index')],
                ['label' => $employee->full_name, 'href' => route('hrms.employees.show', $employee)],
                ['label' => __('Documents'), 'href' => route('hrms.employees.documents.index', $employee)],
                ['label' => __('Upload Document'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('hrms.employees.documents.store', $employee) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <x-forms.field :label="__('Title')" name="title" required>
                <x-forms.input id="title" name="title" :value="old('title')" required />
            </x-forms.field>
            <x-forms.field :label="__('Category')" name="category" required>
                <x-forms.select id="category" name="category" required>
                    <option value="">{{ __('Select category') }}</option>
                    @foreach (config('hrms.document_categories', []) as $key => $label)
                        <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
            </x-forms.field>
            <x-forms.field :label="__('Expiry Date (optional)')" name="expires_at">
                <x-forms.input id="expires_at" type="date" name="expires_at" :value="old('expires_at')" />
            </x-forms.field>
            <x-forms.field :label="__('Notes (optional)')" name="notes">
                <textarea id="notes" name="notes" class="w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="2">{{ old('notes') }}</textarea>
            </x-forms.field>
            <x-forms.field :label="__('File')" name="file" required>
                <input id="file" type="file" name="file" class="block w-full text-sm text-ink-muted file:mr-4 file:rounded-md file:border-0 file:bg-surface-muted file:px-4 file:py-2 file:text-sm file:font-semibold file:text-ink-heading hover:file:bg-neutral-100" required />
            </x-forms.field>
            <x-forms.footer :cancel-href="route('hrms.employees.documents.index', $employee)" :submit-label="__('Upload')" />
        </form>
    </x-layouts.create>
</x-app-layout>
