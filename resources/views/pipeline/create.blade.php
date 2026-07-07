<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Add Deal') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Create a new pipeline opportunity') }}</p>
        </div>
    </x-slot>
    <x-flash-messages />
    <form method="POST" action="{{ route('pipeline.store') }}" class="max-w-4xl">
        @csrf
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 sm:p-8">@include('pipeline._form')</div>
            <div class="px-6 sm:px-8 py-4 border-t border-slate-200 bg-slate-50/50 flex justify-between">
                <a href="{{ route('pipeline.index') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Create Deal') }}</x-primary-button>
            </div>
        </div>
    </form>
</x-app-layout>
