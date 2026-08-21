<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Email Templates')"
        :subtitle="__('Reusable messages for CRM email. Variables are replaced when a template is applied.')"
    >
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('organization.edit', ['tab' => 'email']) }}" class="text-sm text-indigo-600 hover:text-indigo-800">{{ __('← Email settings') }}</a>
        </div>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-5 mb-6">
            <h2 class="font-medium mb-3">{{ __('Create template') }}</h2>
            <form method="POST" action="{{ route('organization.settings.email-templates.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @csrf
                <x-forms.field :label="__('Name')" name="name" required>
                    <x-forms.input name="name" value="{{ old('name') }}" required />
                </x-forms.field>
                <x-forms.field :label="__('Category')" name="category" required>
                    <x-forms.select name="category" required>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', 'general') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="md:col-span-2">
                    <x-forms.field :label="__('Subject')" name="subject" required>
                        <x-forms.input name="subject" value="{{ old('subject') }}" required />
                    </x-forms.field>
                </div>
                <div class="md:col-span-2">
                    <x-forms.field :label="__('Body')" name="body" required>
                        <x-forms.textarea name="body" rows="6" required>{{ old('body') }}</x-forms.textarea>
                    </x-forms.field>
                </div>
                <div class="md:col-span-2">
                    <p class="mb-2 text-sm font-medium text-ink-heading">{{ __('Available modules') }}</p>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($modules as $value => $label)
                            <label class="inline-flex items-center gap-2 text-sm text-ink">
                                <input type="checkbox" name="available_modules[]" value="{{ $value }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(in_array($value, old('available_modules', ['customers', 'contacts']), true))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked>
                    {{ __('Active') }}
                </label>
                <div class="md:col-span-2">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create template') }}</x-ui.button>
                </div>
            </form>

            <div class="mt-5 border-t border-line pt-4 text-sm text-slate-600">
                <p class="font-medium mb-2">{{ __('Available variables') }}</p>
                <p class="flex flex-wrap gap-2">
                    @foreach ($variables as $key => $label)
                        <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs" title="{{ $label }}">{{ '{{'.$key.'}}' }}</code>
                    @endforeach
                </p>
            </div>
        </div>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-3 text-left">{{ __('Name') }}</th>
                        <th class="p-3 text-left">{{ __('Category') }}</th>
                        <th class="p-3 text-left">{{ __('Status') }}</th>
                        <th class="p-3 text-left">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($templates as $template)
                    <tr class="border-t align-top">
                        <td class="p-3">
                            <p class="font-medium text-ink-heading">{{ $template->name }}</p>
                            <p class="text-xs text-ink-muted">{{ $template->subject }}</p>
                        </td>
                        <td class="p-3">{{ $template->categoryLabel() }}</td>
                        <td class="p-3">
                            <x-ui.badge :variant="$template->is_active ? 'success' : 'neutral'">
                                {{ $template->is_active ? __('Active') : __('Inactive') }}
                            </x-ui.badge>
                        </td>
                        <td class="p-3">
                            <details class="rounded-lg border border-line p-3">
                                <summary class="cursor-pointer text-sm font-medium text-indigo-700">{{ __('Edit') }}</summary>
                                <form method="POST" action="{{ route('organization.settings.email-templates.update', $template) }}" class="mt-3 grid grid-cols-1 gap-3">
                                    @csrf
                                    @method('PUT')
                                    <x-forms.input name="name" value="{{ old('name', $template->name) }}" required />
                                    <x-forms.select name="category" required>
                                        @foreach ($categories as $value => $label)
                                            <option value="{{ $value }}" @selected(old('category', $template->category) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </x-forms.select>
                                    <x-forms.input name="subject" value="{{ old('subject', $template->subject) }}" required />
                                    <x-forms.textarea name="body" rows="5" required>{{ old('body', $template->body) }}</x-forms.textarea>
                                    <div class="flex flex-wrap gap-3">
                                        @foreach ($modules as $value => $label)
                                            <label class="inline-flex items-center gap-2 text-sm">
                                                <input type="checkbox" name="available_modules[]" value="{{ $value }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(in_array($value, old('available_modules', $template->available_modules ?? []), true))>
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked($template->is_active)>
                                        {{ __('Active') }}
                                    </label>
                                    <div class="flex gap-2">
                                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save') }}</x-ui.button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('organization.settings.email-templates.destroy', $template) }}" class="mt-2" onsubmit="return confirm('{{ __('Delete this template?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-ui.button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-6 text-center text-ink-muted">{{ __('No email templates yet.') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($templates->hasPages())
            <div class="mt-4">{{ $templates->links() }}</div>
        @endif
    </x-layouts.settings>
</x-app-layout>
