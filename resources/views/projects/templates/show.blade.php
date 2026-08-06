<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail :title="__('Details Templates')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Details Templates'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5 space-y-3">
        <p class="text-sm text-slate-600">{{ $template->description }}</p>
        @if ($template->category)
            <p class="text-xs text-slate-500">{{ __('Category') }}: {{ $template->category }}</p>
        @endif
        <p class="text-xs text-slate-500">{{ __('Milestones') }}: {{ $template->templateMilestones->count() }} · {{ __('Tasks') }}: {{ $template->templateTasks->count() }}</p>
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
