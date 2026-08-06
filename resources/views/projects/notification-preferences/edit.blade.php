@php
    $frequencies = config('projects.notification_digest_frequencies', [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
    ]);
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit :title="__('Edit Notification preferences')" max-width="4xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Edit Notification preferences'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <form method="POST" action="{{ route('notification-preferences.update') }}" class="max-w-3xl">
        @csrf
        @method('PUT')

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Channels') }}</h3>
            </div>
            <div class="p-6 space-y-4">
                <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3">
                    <span>
                        <span class="block text-sm font-medium text-slate-900">{{ __('In-app notifications') }}</span>
                        <span class="block text-xs text-slate-500 mt-0.5">{{ __('Show alerts inside :product', ['product' => config('branding.product_name')]) }}</span>
                    </span>
                    <input type="hidden" name="in_app_enabled" value="0" />
                    <input type="checkbox" name="in_app_enabled" value="1" @checked(old('in_app_enabled', $preference->in_app_enabled)) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                </label>
                <x-input-error :messages="$errors->get('in_app_enabled')" />

                <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3">
                    <span>
                        <span class="block text-sm font-medium text-slate-900">{{ __('Email notifications') }}</span>
                        <span class="block text-xs text-slate-500 mt-0.5">{{ __('Send email for project and task updates') }}</span>
                    </span>
                    <input type="hidden" name="email_enabled" value="0" />
                    <input type="checkbox" name="email_enabled" value="1" @checked(old('email_enabled', $preference->email_enabled)) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                </label>
                <x-input-error :messages="$errors->get('email_enabled')" />

                <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3">
                    <span>
                        <span class="block text-sm font-medium text-slate-900">{{ __('Digest emails') }}</span>
                        <span class="block text-xs text-slate-500 mt-0.5">{{ __('Bundle updates into a periodic digest') }}</span>
                    </span>
                    <input type="hidden" name="digest_enabled" value="0" />
                    <input type="checkbox" name="digest_enabled" value="1" @checked(old('digest_enabled', $preference->digest_enabled)) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                </label>
                <x-input-error :messages="$errors->get('digest_enabled')" />

                <div>
                    <x-input-label for="digest_frequency" :value="__('Digest frequency')" />
                    <select id="digest_frequency" name="digest_frequency" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                        @foreach ($frequencies as $value => $label)
                            <option value="{{ $value }}" @selected(old('digest_frequency', $preference->digest_frequency) === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('digest_frequency')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Muted') }}</h3>
                <p class="text-sm text-slate-500 mt-0.5">{{ __('Comma-separated IDs to mute (optional)') }}</p>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <x-input-label for="muted_projects" :value="__('Muted project IDs')" />
                    <x-text-input
                        id="muted_projects"
                        name="muted_projects_csv"
                        class="block mt-1 w-full"
                        :value="old('muted_projects_csv', implode(', ', $preference->muted_projects ?? []))"
                        placeholder="1, 2, 3"
                    />
                    @foreach (old('muted_projects', $preference->muted_projects ?? []) as $projectId)
                        <input type="hidden" name="muted_projects[]" value="{{ $projectId }}" class="muted-project-hidden" />
                    @endforeach
                    <x-input-error :messages="$errors->get('muted_projects')" class="mt-2" />
                    <x-input-error :messages="$errors->get('muted_projects.*')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="muted_tasks" :value="__('Muted task IDs')" />
                    <x-text-input
                        id="muted_tasks"
                        name="muted_tasks_csv"
                        class="block mt-1 w-full"
                        :value="old('muted_tasks_csv', implode(', ', $preference->muted_tasks ?? []))"
                        placeholder="10, 20"
                    />
                    @foreach (old('muted_tasks', $preference->muted_tasks ?? []) as $taskId)
                        <input type="hidden" name="muted_tasks[]" value="{{ $taskId }}" class="muted-task-hidden" />
                    @endforeach
                    <x-input-error :messages="$errors->get('muted_tasks')" class="mt-2" />
                    <x-input-error :messages="$errors->get('muted_tasks.*')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <x-primary-button type="submit">{{ __('Save Preferences') }}</x-primary-button>
        </div>
    </form>

    <script>
        (function () {
            function syncCsvToHidden(csvInputId, hiddenClass, name) {
                const input = document.getElementById(csvInputId);
                if (!input) return;
                const container = input.parentElement;
                const sync = () => {
                    container.querySelectorAll('.' + hiddenClass).forEach((el) => el.remove());
                    String(input.value || '')
                        .split(',')
                        .map((v) => v.trim())
                        .filter(Boolean)
                        .forEach((id) => {
                            const hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = name;
                            hidden.value = id;
                            hidden.className = hiddenClass;
                            container.appendChild(hidden);
                        });
                };
                input.addEventListener('change', sync);
                input.addEventListener('blur', sync);
                input.form?.addEventListener('submit', sync);
            }
            syncCsvToHidden('muted_projects', 'muted-project-hidden', 'muted_projects[]');
            syncCsvToHidden('muted_tasks', 'muted-task-hidden', 'muted_tasks[]');
        })();
    </script>
    </x-layouts.edit>
</x-app-layout>
