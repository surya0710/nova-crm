<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Statuses')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Statuses'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($statuses->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No task statuses defined yet.') }}</div>
        @else
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Closed') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($statuses as $status)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $status->name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $status->is_closed ? __('Yes') : __('No') }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="{{ route('task-statuses.edit', $status) }}" class="text-primary-600 hover:underline">{{ __('Edit') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-6 py-3">{{ $statuses->links() }}</div>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
