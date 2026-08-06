<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit :title="__('Edit Templates')" max-width="4xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Edit Templates'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
        <form method="POST" action="{{ route('project-templates.update', $template) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('projects.templates._form', ['template' => $template])
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </form>
    </div>
    </x-layouts.edit>
</x-app-layout>
