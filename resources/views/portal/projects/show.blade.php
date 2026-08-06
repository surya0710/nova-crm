<x-layouts.portal>
    <div class="space-y-6">
        <div>
            <a href="{{ route('portal.dashboard', $portalOrganization) }}" class="text-sm text-slate-500 hover:text-slate-800">← {{ __('Dashboard') }}</a>
            <h1 class="mt-2 text-2xl font-semibold">{{ $payload['project']['name'] }}</h1>
            @if (!empty($payload['project']['description']))
                <p class="mt-2 text-sm text-slate-600 whitespace-pre-wrap">{{ $payload['project']['description'] }}</p>
            @endif
        </div>

        @if (!empty($payload['milestones']))
            <section class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="font-medium">{{ __('Milestones') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($payload['milestones'] as $milestone)
                        <li class="flex justify-between gap-3"><span>{{ $milestone->name }}</span><span class="text-slate-500">{{ $milestone->due_date?->toDateString() }}</span></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (!empty($payload['deliverables']))
            <section class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="font-medium">{{ __('Deliverables') }}</h2>
                <ul class="mt-3 divide-y divide-slate-100">
                    @foreach ($payload['deliverables'] as $deliverable)
                        <li class="py-3 flex justify-between gap-3 text-sm">
                            <div>
                                <div class="font-medium">{{ $deliverable->title }}</div>
                                <div class="text-slate-500">{{ $deliverable->status_label }}</div>
                            </div>
                            <a class="underline" href="{{ route('portal.deliverables.show', [$portalOrganization, $deliverable]) }}">{{ __('View') }}</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (!empty($payload['discussions']))
            <section class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="font-medium">{{ __('Discussions') }}</h2>
                <ul class="mt-3 space-y-3 text-sm">
                    @foreach ($payload['discussions'] as $discussion)
                        <li class="rounded-lg bg-slate-50 p-3">
                            <div class="text-xs text-slate-500">{{ $discussion->authorClient?->name ?? $discussion->authorUser?->name }} · {{ $discussion->created_at }}</div>
                            <div class="mt-1 whitespace-pre-wrap">{{ $discussion->body }}</div>
                        </li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('portal.discussions.store', [$portalOrganization, $project]) }}" class="mt-4 space-y-2">
                    @csrf
                    <textarea name="body" rows="3" class="w-full rounded-lg border-slate-300" required placeholder="{{ __('Write a message…') }}"></textarea>
                    <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm text-white">{{ __('Post') }}</button>
                </form>
            </section>
        @endif

        @if (!empty($payload['upload_requests']))
            <section class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="font-medium">{{ __('Upload Requests') }}</h2>
                <ul class="mt-3 space-y-4">
                    @foreach ($payload['upload_requests'] as $uploadRequest)
                        <li class="text-sm">
                            <div class="font-medium">{{ $uploadRequest->title }}</div>
                            <div class="text-slate-500">{{ $uploadRequest->status }}</div>
                            @if ($uploadRequest->status === 'open')
                                <form method="POST" action="{{ route('portal.upload-requests.fulfill', [$portalOrganization, $uploadRequest]) }}" enctype="multipart/form-data" class="mt-2 flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="file" name="file" required>
                                    <button class="rounded-lg bg-slate-800 px-3 py-1.5 text-white">{{ __('Upload') }}</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (!empty($payload['invoices']))
            <section class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="font-medium">{{ __('Invoices') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($payload['invoices'] as $invoice)
                        <li class="flex justify-between gap-3">
                            <span>{{ $invoice->number ?? ('#'.$invoice->id) }}</span>
                            <span class="text-slate-500">{{ $invoice->status }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.portal>
