<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">{{ __('Permission Templates') }}</h1>
        <p class="text-sm text-slate-500">{{ __('Install predefined role and permission templates.') }}</p>
    </x-slot>

    <x-flash-messages />
    @include('rbac._nav')

    @can('installTemplate', App\Models\PermissionTemplate::class)
        <form method="POST" action="{{ route('rbac.templates.reset') }}" class="mb-4">
            @csrf
            <button type="submit" class="text-sm text-rose-600 hover:text-rose-800" onclick="return confirm('{{ __('Reset organization roles to default template?') }}')">
                {{ __('Reset to Default Template') }}
            </button>
        </form>
    @endcan

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($templates as $template)
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $template->name }}</h3>
                        <p class="text-sm text-slate-500 mt-1">{{ $template->description }}</p>
                        @if ($template->is_default)
                            <span class="inline-flex mt-2 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ __('Default') }}</span>
                        @endif
                    </div>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('rbac.templates.show', $template) }}" class="text-sm text-indigo-600 hover:text-indigo-800">{{ __('Preview') }}</a>
                        @can('installTemplate', App\Models\PermissionTemplate::class)
                            <form method="POST" action="{{ route('rbac.templates.install') }}">
                                @csrf
                                <input type="hidden" name="template_id" value="{{ $template->id }}">
                                <button type="submit" class="text-sm text-emerald-600 hover:text-emerald-800">{{ __('Install') }}</button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
