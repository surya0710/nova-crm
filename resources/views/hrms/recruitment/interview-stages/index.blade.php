<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Interview Stages')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Interview Stages'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
            <h2 class="font-medium mb-4">{{ __('Pipeline Stages') }}</h2>
            <table class="w-full text-sm">
                <thead><tr class="text-left text-slate-500 border-b"><th class="pb-2">{{ __('Order') }}</th><th class="pb-2">{{ __('Name') }}</th><th class="pb-2">{{ __('Slug') }}</th><th class="pb-2">{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @foreach ($stages as $stage)
                        <tr class="border-b border-slate-100">
                            <td class="py-2">{{ $stage->sort_order }}</td>
                            <td class="py-2">{{ $stage->name }}</td>
                            <td class="py-2 text-slate-500">{{ $stage->slug }}</td>
                            <td class="py-2">{{ $stage->is_active ? __('Active') : __('Inactive') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @can('create', App\Models\InterviewStage::class)
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
            <h2 class="font-medium mb-4">{{ __('Add Custom Stage') }}</h2>
            <form method="POST" action="{{ route('hrms.recruitment.interview-stages.store') }}" class="space-y-3">
                @csrf
                <div><label class="text-sm text-slate-600">{{ __('Slug') }}</label><input name="slug" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required></div>
                <div><label class="text-sm text-slate-600">{{ __('Name') }}</label><input name="name" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required></div>
                <div><label class="text-sm text-slate-600">{{ __('Sort Order') }}</label><input name="sort_order" type="number" min="0" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"></div>
                <button class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">{{ __('Create Stage') }}</button>
            </form>
        </div>
        @endcan
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
