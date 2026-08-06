@include('workflows._assets')

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Create workflow') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Build an event-driven CRM automation') }}</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('workflows.store') }}" class="max-w-6xl mx-auto">
        @csrf
        @include('workflows._form')

        <div class="workflow-bootstrap d-flex justify-content-between align-items-center mt-4">
            <a href="{{ route('workflows.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create draft workflow</button>
        </div>
    </form>
</x-app-layout>
