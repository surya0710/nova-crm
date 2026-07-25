<x-app-layout>
    <x-flash-messages />

    <x-layouts.create :title="__('Create Categories')" max-width="4xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Create Categories'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <form method="POST" action="{{ route('project-categories.store') }}" class="max-w-3xl">
        @csrf
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 sm:p-8">
                @include('projects.categories._form', ['category' => $category])
            </div>
            <div class="px-6 sm:px-8 py-4 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between gap-4">
                <a href="{{ route('project-categories.index') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Create Category') }}</x-primary-button>
            </div>
        </div>
    </form>
    </x-layouts.create>
</x-app-layout>
