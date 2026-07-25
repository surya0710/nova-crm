<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Feedback Campaigns')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Feedback'), 'href' => route('hrms.performance.feedback.index')],
                ['label' => __('Feedback Campaigns'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\FeedbackCampaign::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium text-slate-800 mb-3">{{ __('Create Campaign') }}</h2>
        <form method="POST" action="{{ route('hrms.performance.feedback.campaigns.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            <x-forms.input name="name" placeholder="{{ __('Campaign Name') }}" :value="old('name')" required  />
            <select name="performance_cycle_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Performance Cycle') }}</option>
                @foreach ($cycles as $cycle)
                    <option value="{{ $cycle->id }}" @selected(old('performance_cycle_id') == $cycle->id)>{{ $cycle->name }}</option>
                @endforeach
            </select>
            <select name="feedback_template_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Feedback Template') }}</option>
                @foreach ($templates as $template)
                    <option value="{{ $template->id }}" @selected(old('feedback_template_id') == $template->id)>{{ $template->name }}</option>
                @endforeach
            </select>
            <x-forms.input name="start_date" type="date" :value="old('start_date')" required  />
            <x-forms.input name="due_date" type="date" :value="old('due_date')" required  />
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous', true)) class="rounded border-slate-300" />
                {{ __('Anonymous feedback') }}
            </label>
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Campaign') }}</x-ui.button>
        </form>
    </div>
    @endcan

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Name') }}</th>
                    <th class="p-3 text-left">{{ __('Cycle') }}</th>
                    <th class="p-3 text-left">{{ __('Due') }}</th>
                    <th class="p-3 text-left">{{ __('Anonymous') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($campaigns as $campaign)
                <tr class="border-t">
                    <td class="p-3">{{ $campaign->name }}</td>
                    <td class="p-3">{{ $campaign->cycle?->name }}</td>
                    <td class="p-3">{{ $campaign->due_date?->format('Y-m-d') }}</td>
                    <td class="p-3">{{ $campaign->is_anonymous ? __('Yes') : __('No') }}</td>
                    <td class="p-3">{{ $statuses[$campaign->status] ?? $campaign->status }}</td>
                    <td class="p-3">
                        <a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.feedback.campaigns.show', $campaign) }}">{{ __('View') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-slate-500">{{ __('No campaigns found.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $campaigns->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
