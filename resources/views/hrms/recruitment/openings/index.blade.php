<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Job Openings')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Job Openings'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\JobOpening::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <form method="POST" action="{{ route('hrms.recruitment.openings.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            <select name="job_requisition_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Approved Requisition') }}</option>
                @foreach ($requisitions as $requisition)
                    <option value="{{ $requisition->id }}">{{ $requisition->designation?->name }} (#{{ $requisition->id }})</option>
                @endforeach
            </select>
            <x-forms.input name="title" placeholder="{{ __('Opening Title') }}" required  />
            <x-forms.input name="location" placeholder="{{ __('Location') }}"  />
            <textarea name="description" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="2" placeholder="{{ __('Description') }}"></textarea>
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Opening') }}</x-ui.button>
        </form>
    </div>
    @endcan
    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50"><tr><th class="p-3 text-left">{{ __('Title') }}</th><th class="p-3 text-left">{{ __('Department') }}</th><th class="p-3 text-left">{{ __('Status') }}</th><th class="p-3 text-left">{{ __('Actions') }}</th></tr></thead>
            <tbody>
            @foreach ($openings as $opening)
                <tr class="border-t">
                    <td class="p-3">{{ $opening->title }}</td>
                    <td class="p-3">{{ $opening->department?->name }}</td>
                    <td class="p-3">{{ $opening->statusLabel() }}</td>
                    <td class="p-3"><a href="{{ route('hrms.recruitment.openings.show', $opening) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('View') }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $openings->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
