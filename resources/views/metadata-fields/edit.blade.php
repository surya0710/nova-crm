<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Edit Metadata Field') }}</h1>
            <p class="text-sm text-slate-500">{{ $field->label }}</p>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="max-w-4xl">
        <form method="POST" action="{{ route('metadata-fields.update', $field) }}" class="rounded-xl bg-white border border-slate-200 shadow-sm p-6 sm:p-8">
            @csrf
            @method('PATCH')

            @include('metadata-fields._form')

            <div class="mt-8 flex items-center justify-end gap-3">
                <a href="{{ route('metadata-fields.show', $field) }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save Field') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
