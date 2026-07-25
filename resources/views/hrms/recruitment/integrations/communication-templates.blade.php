<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Communication Templates')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Communication Templates'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @php
        $keys = $templateKeys ?? $keys ?? [];
        $channels = $channels ?? [];
        $variables = $variables ?? [];
    @endphp

    <div class="mb-4">
        <a href="{{ route('hrms.recruitment.integrations.index') }}" class="text-sm text-indigo-600">{{ __('← Integrations') }}</a>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium mb-3">{{ __('Create Template') }}</h2>
        <form method="POST" action="{{ route('hrms.recruitment.communication-templates.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @csrf
            <select name="key" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Template Key') }}</option>
                @foreach ($keys as $key)
                    <option value="{{ $key }}">{{ $key }}</option>
                @endforeach
            </select>
            <x-forms.input name="name" placeholder="{{ __('Name') }}" required  />
            <select name="channel" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Channel') }}</option>
                @foreach ($channels as $channel)
                    <option value="{{ $channel }}">{{ $channel }}</option>
                @endforeach
            </select>
            <x-forms.input name="subject" placeholder="{{ __('Subject') }}"  />
            <textarea name="body" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="4" placeholder="{{ __('Body') }}" required></textarea>
            <div class="md:col-span-2">
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Template') }}</x-ui.button>
            </div>
        </form>
        @if (count($variables))
            <div class="mt-4 text-sm text-slate-600">
                <p class="font-medium mb-1">{{ __('Available variables') }}</p>
                <p class="flex flex-wrap gap-2">
                    @foreach ($variables as $variable)
                        <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ '{{'.$variable.'}}' }}</code>
                    @endforeach
                </p>
            </div>
        @endif
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Key') }}</th>
                    <th class="p-3 text-left">{{ __('Name') }}</th>
                    <th class="p-3 text-left">{{ __('Channel') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($templates as $template)
                <tr class="border-t">
                    <td class="p-3">{{ $template->key }}</td>
                    <td class="p-3">{{ $template->name }}</td>
                    <td class="p-3">{{ $template->channel }}</td>
                    <td class="p-3">{{ $template->statusLabel() }}</td>
                    <td class="p-3">
                        <div class="flex flex-wrap gap-2">
                            @if ($template->status === 'draft')
                                <form method="POST" action="{{ route('hrms.recruitment.communication-templates.submit', $template) }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('Submit') }}</button>
                                </form>
                            @endif
                            @if ($template->status === 'pending_approval')
                                <form method="POST" action="{{ route('hrms.recruitment.communication-templates.approve', $template) }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('Approve') }}</button>
                                </form>
                            @endif
                            @if ($template->status === 'active')
                                <form method="POST" action="{{ route('hrms.recruitment.communication-templates.deactivate', $template) }}">
                                    @csrf
                                    <button type="submit" class="text-rose-600">{{ __('Deactivate') }}</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="border-t"><td class="p-3 text-slate-500" colspan="5">{{ __('No templates yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        @if (method_exists($templates, 'links'))
            <div class="p-4">{{ $templates->links() }}</div>
        @endif
    </div>
    </x-layouts.settings>
</x-app-layout>
