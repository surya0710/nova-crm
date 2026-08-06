<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$template->name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Offer Templates'), 'href' => route('hrms.recruitment.offer-templates.index')],
                ['label' => $template->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4">
            <div><dt class="text-slate-500">{{ __('Department') }}</dt><dd>{{ $template->department?->name ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Designation') }}</dt><dd>{{ $template->designation?->name ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Employment Type') }}</dt><dd>{{ $template->employmentTypeLabel() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Status') }}</dt><dd>{{ $template->is_active ? __('Active') : __('Inactive') }}</dd></div>
        </dl>
        <h2 class="font-medium mb-2">{{ __('Template Content') }}</h2>
        <pre class="text-sm whitespace-pre-wrap bg-slate-50 p-4 rounded-md">{{ $template->template_content }}</pre>
    </div>
    @can('update', $template)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
        <h2 class="font-medium mb-4">{{ __('Edit Template') }}</h2>
        <form method="POST" action="{{ route('hrms.recruitment.offer-templates.update', $template) }}" class="space-y-3">
            @csrf @method('PUT')
            <div><label class="text-sm text-slate-600">{{ __('Name') }}</label><input name="name" value="{{ $template->name }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required></div>
            <div><label class="text-sm text-slate-600">{{ __('Template Content') }}</label><textarea name="template_content" rows="8" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>{{ $template->template_content }}</textarea></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($template->is_active)> {{ __('Active') }}</label>
            <button class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">{{ __('Save') }}</button>
        </form>
    </div>
    @endcan
    </x-layouts.entity-detail>
</x-app-layout>
