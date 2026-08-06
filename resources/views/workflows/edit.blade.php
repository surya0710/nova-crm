@include('workflows._assets')

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Edit workflow') }}</h1>
            <p class="text-sm text-slate-500">{{ $workflow->name }} · {{ __('Version :version', ['version' => $workflow->version]) }}</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('workflows.update', $workflow) }}" class="max-w-6xl mx-auto">
        @csrf
        @method('PUT')
        @include('workflows._form')

        <div class="workflow-bootstrap d-flex justify-content-between align-items-center mt-4">
            <a href="{{ route('workflows.show', $workflow) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save workflow</button>
        </div>
    </form>
</x-app-layout>
