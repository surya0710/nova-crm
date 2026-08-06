<x-app-layout>
    <x-flash-messages />
    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('projects.deliverables.index', $project) }}" class="text-sm underline">← {{ __('Deliverables') }}</a>
                <h1 class="mt-2 text-xl font-semibold">{{ $deliverable->title }}</h1>
                <p class="text-sm text-slate-500">{{ $deliverable->status_label }}</p>
            </div>

            @if ($deliverable->description)
                <p class="whitespace-pre-wrap text-sm text-slate-700">{{ $deliverable->description }}</p>
            @endif

            <div class="flex flex-wrap gap-3">
                @if (in_array($deliverable->status, ['draft', 'revised', 'rejected'], true))
                    <form method="POST" action="{{ route('projects.deliverables.submit', [$project, $deliverable]) }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-white p-4 space-y-2">
                        @csrf
                        <div class="font-medium text-sm">{{ __('Submit') }}</div>
                        <input type="file" name="file">
                        <textarea name="notes" rows="2" class="w-full rounded-lg border-slate-300" placeholder="{{ __('Notes') }}"></textarea>
                        <button class="rounded-lg bg-indigo-600 px-3 py-2 text-white text-sm">{{ __('Submit to portal') }}</button>
                    </form>
                @endif

                @if (in_array($deliverable->status, ['submitted', 'client_review', 'revised', 'draft'], true))
                    <form method="POST" action="{{ route('projects.deliverables.request-approval', [$project, $deliverable]) }}" class="rounded-xl border border-slate-200 bg-white p-4 space-y-2">
                        @csrf
                        <div class="font-medium text-sm">{{ __('Request client approval') }}</div>
                        <textarea name="request_message" rows="2" class="w-full rounded-lg border-slate-300" placeholder="{{ __('Message to client') }}"></textarea>
                        <button class="rounded-lg bg-amber-600 px-3 py-2 text-white text-sm">{{ __('Request approval') }}</button>
                    </form>
                @endif
            </div>

            @if ($deliverable->versions->isNotEmpty())
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <h2 class="font-medium">{{ __('Versions') }}</h2>
                    <ul class="mt-2 text-sm space-y-1">
                        @foreach ($deliverable->versions as $version)
                            <li>{{ $version->label }} — {{ $version->original_name }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
