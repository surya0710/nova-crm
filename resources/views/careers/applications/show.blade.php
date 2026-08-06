<x-careers-layout>
    <div class="rounded-xl border bg-white p-6">
        <h1 class="text-2xl font-semibold">{{ $application->jobOpening?->title }}</h1>
        <p class="text-slate-500 mt-1">{{ __('Status') }}: {{ $application->portalStatusLabel() }}</p>

        <section class="mt-6">
            <h2 class="font-semibold">{{ __('Application timeline') }}</h2>
            <ol class="mt-3 space-y-2">@foreach($timeline as $step)
                <li class="flex items-center gap-2 text-sm @if($step['reached']) text-indigo-700 font-medium @else text-slate-400 @endif">
                    <span class="h-2 w-2 rounded-full @if($step['reached']) bg-indigo-600 @else bg-slate-300 @endif"></span>{{ $step['label'] }}
                </li>
            @endforeach</ol>
        </section>

        @if($application->is_draft)
            <form method="POST" action="{{ route('careers.applications.submit', [$organization, $application]) }}" class="mt-6">@csrf<button class="rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Submit application') }}</button></form>
        @endif

        @if($application->canCandidateEdit() && !$application->is_draft)
            <form method="POST" action="{{ route('careers.applications.withdraw', [$organization, $application]) }}" class="mt-6" onsubmit="return confirm('{{ __('Withdraw this application?') }}')">@csrf<button class="rounded-lg border border-red-300 text-red-700 px-4 py-2">{{ __('Withdraw application') }}</button></form>
        @endif
    </div>
</x-careers-layout>
