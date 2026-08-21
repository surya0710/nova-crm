<x-app-layout>
    <x-layouts.entity-listing
        :title="__('CRM Activities')"
        :subtitle="__('Follow-ups, notes, and assignments')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Activities'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-ui.tabs
            class="mb-6"
            :tabs="[
                ['label' => __('List'), 'href' => route('crm.activities', ['view' => 'list']), 'active' => $view === 'list'],
                ['label' => __('Timeline'), 'href' => route('crm.activities', ['view' => 'timeline']), 'active' => $view === 'timeline'],
                ['label' => __('Calendar'), 'href' => route('crm.activities', ['view' => 'calendar']), 'active' => $view === 'calendar'],
            ]"
        />

        @if ($view === 'list')
            <form method="GET" action="{{ route('crm.activities') }}" class="mb-4 flex flex-wrap gap-2">
                <input type="hidden" name="view" value="list">
                @foreach (['mine' => __('My activities'), 'upcoming' => __('Upcoming'), 'overdue' => __('Overdue'), 'completed' => __('Completed'), 'all' => __('All')] as $scope => $label)
                    <a href="{{ route('crm.activities', array_merge(request()->except('page'), ['view' => 'list', 'scope' => $scope])) }}"
                       @class(['rounded-full border px-3 py-1.5 text-xs', 'border-primary-300 bg-primary-50 text-primary-700' => ($filters['scope'] ?? '') === $scope, 'border-line' => ($filters['scope'] ?? '') !== $scope])>{{ $label }}</a>
                @endforeach
                <x-forms.select name="type" onchange="this.form.submit()">
                    <option value="">{{ __('All types') }}</option>
                    @foreach (config('crm_activities.types') ?? [] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
            </form>

            <x-entity.section class="mb-6" :title="__('Sales activities')" :subtitle="__('Tasks, calls, meetings, follow-ups, notes, and emails')">
                @forelse ($salesActivities as $activity)
                    <div class="flex items-start justify-between gap-3 border-b border-line py-2.5 last:border-0">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink-heading">{{ $activity->subject }}</p>
                            <p class="text-xs text-ink-muted">
                                {{ $activity->type_label }}
                                @if ($activity->customer) · {{ $activity->customer->display_name }} @endif
                                @if ($activity->opportunity) · {{ $activity->opportunity->title }} @endif
                                @if ($activity->assignee) · {{ $activity->assignee->name }} @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($activity->isOverdue())
                                <x-ui.badge variant="danger">{{ __('Overdue') }}</x-ui.badge>
                            @else
                                <x-ui.badge variant="neutral">{{ $activity->status_label }}</x-ui.badge>
                            @endif
                            @if ($activity->isOpen())
                                <form method="POST" action="{{ route('crm.activities.complete', $activity) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Complete') }}</x-ui.button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-sm text-ink-muted">{{ __('No sales activities in this view.') }}</p>
                @endforelse
                @if ($salesActivities->hasPages())
                    <div class="mt-3">{{ $salesActivities->links() }}</div>
                @endif
            </x-entity.section>

            @if ($weekStrip->isNotEmpty())
                <div class="mb-6 grid grid-cols-7 gap-2" aria-label="{{ __('This week\'s follow-ups') }}">
                    @foreach ($weekStrip as $day)
                        <div @class([
                            'rounded-lg border px-2 py-3 text-center',
                            'border-primary-300 bg-primary-50' => $day['is_today'],
                            'border-line bg-surface-card' => ! $day['is_today'],
                        ])>
                            <p class="text-[11px] font-medium text-ink-muted">{{ $day['label'] }}</p>
                            <p @class([
                                'mt-1 text-lg font-semibold',
                                'text-primary-700' => $day['count'] > 0,
                                'text-ink-muted' => $day['count'] === 0,
                            ])>{{ $day['count'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                <x-entity.section :title="__('Due follow-ups')" :subtitle="__('Requires attention now')">
                    @forelse ($dueFollowUps as $item)
                        <a href="{{ $item['url'] }}" class="flex items-start justify-between gap-3 py-2.5 border-b border-line last:border-0">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-ink-heading">{{ $item['name'] }}</p>
                                <p class="text-xs text-ink-muted">{{ $item['company'] ?: ($item['assigned_to'] ?? __('Unassigned')) }}</p>
                            </div>
                            <x-ui.badge variant="warning">{{ $item['next_follow_up_at_formatted'] ?? __('Due') }}</x-ui.badge>
                        </a>
                    @empty
                        <x-ui.empty-state-preset variant="activities" class="!py-6" />
                    @endforelse
                </x-entity.section>

                <x-entity.section :title="__('Today\'s follow-ups')">
                    @forelse ($todaysFollowUps as $lead)
                        <a href="{{ route('leads.show', $lead) }}" class="flex items-start justify-between gap-3 py-2.5 border-b border-line last:border-0">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-ink-heading">{{ $lead->name }}</p>
                                <p class="text-xs text-ink-muted">{{ $lead->company ?: '—' }}</p>
                            </div>
                            <span class="text-xs text-ink-muted">
                                {{ $lead->next_follow_up_at?->timezone(app(\App\Services\LeadFollowUpService::class)->organizationTimezone())->format('g:i A') }}
                            </span>
                        </a>
                    @empty
                        <x-ui.empty-state-preset variant="activities" class="!py-6" />
                    @endforelse
                </x-entity.section>
            </div>

            <div class="mt-6">
                <x-entity.section :title="__('Recent lead notes')">
                    <x-activity.timeline empty-title="{{ __('No notes yet') }}">
                        @foreach ($recentNotes as $note)
                            <x-activity.timeline-item
                                :actor="$note->user?->name"
                                :timestamp="$note->created_at"
                                :label="$note->lead?->name"
                            >{{ $note->body }}</x-activity.timeline-item>
                        @endforeach
                    </x-activity.timeline>
                </x-entity.section>
            </div>
        @elseif ($view === 'timeline')
            <x-entity.section :title="__('Activity timeline')" :subtitle="__('Follow-ups and notes, newest first')">
                <x-activity.timeline empty-title="{{ __('No activity yet') }}" empty-description="{{ __('Follow-ups and notes will appear here.') }}">
                    @foreach ($timelineItems as $item)
                        <x-activity.timeline-item
                            :actor="$item['actor']"
                            :timestamp="$item['timestamp']"
                            :label="$item['label']"
                            :type="$item['type']"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <x-ui.badge :variant="$item['type'] === 'follow_up' ? 'warning' : 'info'" class="mb-1">
                                        {{ $item['type'] === 'follow_up' ? __('Follow-up') : __('Note') }}
                                    </x-ui.badge>
                                    <div>{{ $item['body'] }}</div>
                                </div>
                                @if ($item['url'])
                                    <x-ui.button :href="$item['url']" variant="link" size="sm">{{ __('Open') }}</x-ui.button>
                                @endif
                            </div>
                        </x-activity.timeline-item>
                    @endforeach
                </x-activity.timeline>
            </x-entity.section>
        @else
            <x-entity.section :title="__('Follow-up calendar')" :subtitle="__('Next 14 days')">
                @php $hasAny = $calendarGroups->contains(fn ($group) => $group['items']->isNotEmpty()); @endphp
                @if (! $hasAny)
                    <x-ui.empty-state-preset variant="activities" class="!py-6" />
                @else
                    <div class="space-y-4">
                        @foreach ($calendarGroups as $group)
                            <div @class([
                                'rounded-lg border border-line p-4',
                                'bg-surface-muted/30' => $group['items']->isEmpty(),
                            ])>
                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <h3 class="text-sm font-semibold text-ink-heading">{{ $group['label'] }}</h3>
                                    <x-ui.badge :variant="$group['items']->isEmpty() ? 'neutral' : 'primary'">
                                        {{ $group['items']->count() }}
                                    </x-ui.badge>
                                </div>
                                @forelse ($group['items'] as $lead)
                                    <a href="{{ route('leads.show', $lead) }}" class="flex items-start justify-between gap-3 py-2 border-b border-line last:border-0">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-ink-heading">{{ $lead->name }}</p>
                                            <p class="text-xs text-ink-muted">{{ $lead->assignee?->name ?? __('Unassigned') }}</p>
                                        </div>
                                        <span class="text-xs text-ink-muted shrink-0">
                                            {{ $lead->next_follow_up_at?->timezone(app(\App\Services\LeadFollowUpService::class)->organizationTimezone())->format('g:i A') }}
                                        </span>
                                    </a>
                                @empty
                                    <p class="text-xs text-ink-muted">{{ __('No follow-ups') }}</p>
                                @endforelse
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-entity.section>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
