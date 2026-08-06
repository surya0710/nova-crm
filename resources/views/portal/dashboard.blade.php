<x-layouts.portal>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('Welcome, :name', ['name' => $dashboard['client']['name']]) }}</h1>
            <p class="text-sm text-slate-500">{{ __('Your shared projects and pending actions.') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['label' => __('Active Projects'), 'value' => $dashboard['widgets']['active_projects']],
                ['label' => __('Pending Approvals'), 'value' => $dashboard['widgets']['pending_approvals']],
                ['label' => __('Recent Deliverables'), 'value' => $dashboard['widgets']['recent_deliverables']],
                ['label' => __('Upcoming Milestones'), 'value' => $dashboard['widgets']['upcoming_milestones']],
                ['label' => __('Upload Requests'), 'value' => $dashboard['widgets']['open_upload_requests']],
                ['label' => __('Invoices'), 'value' => $dashboard['widgets']['invoice_count']],
            ] as $widget)
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="text-xs uppercase tracking-wide text-slate-500">{{ $widget['label'] }}</div>
                    <div class="mt-2 text-2xl font-semibold">{{ $widget['value'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3 font-medium">{{ __('Shared Projects') }}</div>
            <ul class="divide-y divide-slate-100">
                @forelse ($dashboard['projects'] as $project)
                    <li class="px-4 py-3 flex items-center justify-between gap-3">
                        <div>
                            <div class="font-medium">{{ $project['name'] }}</div>
                            <div class="text-xs text-slate-500">{{ $project['status'] ?? '—' }}</div>
                        </div>
                        <a href="{{ route('portal.projects.show', [$portalOrganization, $project['id']]) }}" class="text-sm text-slate-700 underline">{{ __('Open') }}</a>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No projects have been shared with you yet.') }}</li>
                @endforelse
            </ul>
        </div>

        @if ($dashboard['pending_approvals']->isNotEmpty())
            <div class="rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-3 font-medium">{{ __('Pending Approvals') }}</div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($dashboard['pending_approvals'] as $approval)
                        <li class="px-4 py-3 text-sm">
                            {{ $approval->approvable->title ?? __('Approval #:id', ['id' => $approval->id]) }}
                            @if ($approval->approvable)
                                — <a class="underline" href="{{ route('portal.deliverables.show', [$portalOrganization, $approval->approvable]) }}">{{ __('Review') }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-layouts.portal>
