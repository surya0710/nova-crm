<x-app-layout>
    <x-flash-messages />

    <x-layouts.create :title="__('Create Templates')" max-width="4xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Create Templates'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
        <form method="POST" action="{{ route('project-templates.store') }}" class="space-y-4">
            @csrf
            @include('projects.templates._form', ['template' => $template])
            <x-primary-button>{{ __('Create') }}</x-primary-button>
        </form>
    </div>
    </x-layouts.create>
</x-app-layout>
