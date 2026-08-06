<x-layouts.portal>
    <div class="space-y-6">
        <div>
            <a href="{{ route('portal.projects.show', [$portalOrganization, $deliverable->project_id]) }}" class="text-sm text-slate-500">← {{ __('Back to project') }}</a>
            <h1 class="mt-2 text-2xl font-semibold">{{ $deliverable->title }}</h1>
            <p class="text-sm text-slate-500">{{ $deliverable->status_label }}</p>
            @if ($deliverable->description)
                <p class="mt-3 whitespace-pre-wrap text-sm text-slate-700">{{ $deliverable->description }}</p>
            @endif
        </div>

        @if ($deliverable->versions->isNotEmpty())
            <section class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="font-medium">{{ __('Versions') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($deliverable->versions as $version)
                        <li>{{ $version->label ?? ('v'.$version->version_number) }} — {{ $version->original_name }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($approval)
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <h2 class="font-medium">{{ __('Action required') }}</h2>
                <p class="mt-1 text-sm">{{ $approval->request_message ?? __('Please approve or reject this deliverable.') }}</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('portal.approvals.approve', [$portalOrganization, $approval]) }}" class="space-y-2">
                        @csrf
                        <textarea name="decision_notes" rows="2" class="w-full rounded-lg border-slate-300" placeholder="{{ __('Notes (optional)') }}"></textarea>
                        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-white">{{ __('Approve') }}</button>
                    </form>
                    <form method="POST" action="{{ route('portal.approvals.reject', [$portalOrganization, $approval]) }}" class="space-y-2">
                        @csrf
                        <textarea name="decision_notes" rows="2" class="w-full rounded-lg border-slate-300" placeholder="{{ __('Reason (optional)') }}"></textarea>
                        <button class="rounded-lg bg-rose-600 px-4 py-2 text-white">{{ __('Reject') }}</button>
                    </form>
                </div>
            </section>
        @endif
    </div>
</x-layouts.portal>
