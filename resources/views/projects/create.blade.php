<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('Add Project')"
        :subtitle="__('Create a new project')"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('All projects'), 'href' => route('projects.index')],
                ['label' => __('Add Project'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('projects.store') }}">
            @csrf
            @include('projects._form', [
                'project' => $project,
                'categories' => $categories,
                'types' => $types,
                'statuses' => $statuses,
                'stages' => $stages,
                'clients' => $clients,
                'departments' => $departments,
                'users' => $users,
                'metadataFields' => $metadataFields ?? null,
                'metadataPresenter' => $metadataPresenter ?? null,
            ])
            <x-forms.footer :cancel-href="route('projects.index')" :submit-label="__('Create Project')" />
        </form>
    </x-layouts.create>
</x-app-layout>
