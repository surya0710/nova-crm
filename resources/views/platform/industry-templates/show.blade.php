<x-platform-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-lg font-semibold">{{ $template->name }}</h1>
            <a href="{{ route('platform.industry-templates.index') }}" class="text-sm text-slate-400 hover:text-white">{{ __('Back to templates') }}</a>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-rose-900/40 border border-rose-700 text-rose-200 px-4 py-3 text-sm">
            <div class="font-medium mb-1">{{ __('Please fix the requested action.') }}</div>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php $platformUser = auth('platform')->user(); @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4">
            <h2 class="font-medium text-white">{{ __('Template Summary') }}</h2>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">{{ __('Slug') }}</dt><dd class="text-slate-200">{{ $template->slug }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Industry') }}</dt><dd class="text-slate-200">{{ $template->industry ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Status') }}</dt><dd class="text-slate-200">{{ $template->statusLabel() }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Visibility') }}</dt><dd class="text-slate-200">{{ $template->visibilityLabel() }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Current Version') }}</dt><dd class="text-slate-200">{{ $template->currentVersion ? 'v'.$template->currentVersion->version : '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Applications') }}</dt><dd class="text-slate-200">{{ $template->applications_count }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Created By') }}</dt><dd class="text-slate-200">{{ $template->creator?->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Published At') }}</dt><dd class="text-slate-200">{{ $template->published_at?->format('M j, Y H:i') ?? '—' }}</dd></div>
            </dl>
            @if ($template->description)
                <p class="text-sm text-slate-400">{{ $template->description }}</p>
            @endif
        </div>

        <div class="rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-3">
            <h2 class="font-medium text-white">{{ __('Actions') }}</h2>
            @if ($platformUser->hasPermission('platform.industry_templates.update'))
                <a href="{{ route('platform.industry-templates.edit', $template) }}" class="block text-center rounded-lg bg-slate-800 hover:bg-slate-700 px-4 py-2 text-sm">{{ __('Edit Draft') }}</a>
            @endif

            @if ($platformUser->hasPermission('platform.industry_templates.publish'))
                <form method="POST" action="{{ route('platform.industry-templates.publish', $template) }}" class="space-y-2">
                    @csrf
                    <textarea name="changelog" rows="2" placeholder="{{ __('Publish changelog') }}"
                        class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm"></textarea>
                    <button class="w-full rounded-lg bg-violet-600 hover:bg-violet-500 px-4 py-2 text-sm">{{ __('Publish New Version') }}</button>
                </form>
            @endif

            @if ($platformUser->hasPermission('platform.industry_templates.archive'))
                @if ($template->status === 'published')
                    <form method="POST" action="{{ route('platform.industry-templates.inactivate', $template) }}">@csrf<button class="w-full rounded-lg bg-amber-600/20 text-amber-300 border border-amber-700 px-4 py-2 text-sm">{{ __('Inactivate') }}</button></form>
                @endif
                @if (in_array($template->status, ['inactive', 'archived'], true))
                    <form method="POST" action="{{ route('platform.industry-templates.reactivate', $template) }}">@csrf<button class="w-full rounded-lg bg-emerald-600/20 text-emerald-300 border border-emerald-700 px-4 py-2 text-sm">{{ __('Reactivate') }}</button></form>
                @endif
                @if ($template->status !== 'archived')
                    <form method="POST" action="{{ route('platform.industry-templates.archive', $template) }}" onsubmit="return confirm('{{ __('Archive this template?') }}')">@csrf<button class="w-full rounded-lg bg-slate-700 text-slate-200 px-4 py-2 text-sm">{{ __('Archive') }}</button></form>
                @endif
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-800 font-medium">{{ __('Version History') }}</div>
            <div class="divide-y divide-slate-800 text-sm">
                @forelse ($template->versions as $version)
                    <div class="px-5 py-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-white">{{ __('Version :version', ['version' => $version->version]) }} · {{ $version->statusLabel() }}</div>
                                <div class="text-xs text-slate-500">{{ $version->published_at->format('M j, Y H:i') }} · {{ $version->payload_hash }}</div>
                            </div>
                            @if ($platformUser->hasPermission('platform.industry_templates.create'))
                                <form method="POST" action="{{ route('platform.industry-template-versions.clone', $version) }}" class="flex gap-2">
                                    @csrf
                                    <input type="hidden" name="name" value="{{ $template->name }} Copy">
                                    <button class="text-xs rounded bg-slate-800 hover:bg-slate-700 px-3 py-1.5">{{ __('Clone') }}</button>
                                </form>
                            @endif
                        </div>
                        @if ($version->changelog)
                            <p class="text-slate-400">{{ $version->changelog }}</p>
                        @endif
                    </div>
                @empty
                    <div class="px-5 py-6 text-slate-500 text-center">{{ __('No published versions yet.') }}</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-800 font-medium">{{ __('Draft Payload Preview') }}</div>
            <pre class="max-h-[32rem] overflow-auto p-5 text-xs text-slate-300 bg-slate-950">{{ json_encode($template->draft_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
</x-platform-layout>
