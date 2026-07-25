<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Modules & Features')"
        :subtitle="__('Plan modules, feature toggles, and workspace defaults')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Modules'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-ui.card class="mb-6">
            <p class="text-sm text-ink-muted">{{ __('Current plan') }}</p>
            <p class="mt-1 text-lg font-semibold text-ink-heading">{{ ucfirst($overview['plan']) }}</p>
        </x-ui.card>

        <x-entity.section :title="__('Modules')" :subtitle="__('Read-only — managed by plan and platform licensing')">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-ink-muted">
                            <th class="py-2 pr-4">{{ __('Module') }}</th>
                            <th class="py-2 pr-4">{{ __('Plan allows') }}</th>
                            <th class="py-2 pr-4">{{ __('Enabled') }}</th>
                            <th class="py-2">{{ __('Source') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($overview['modules'] as $module)
                            <tr>
                                <td class="py-2.5 pr-4">
                                    <p class="font-medium text-ink-heading">{{ $module['label'] }}</p>
                                    @if (! empty($module['description']))
                                        <p class="mt-0.5 text-xs text-ink-muted">{{ $module['description'] }}</p>
                                    @endif
                                </td>
                                <td class="py-2.5 pr-4">
                                    <x-ui.badge :variant="$module['plan_allows'] ? 'success' : 'neutral'">
                                        {{ $module['plan_allows'] ? __('Yes') : __('No') }}
                                    </x-ui.badge>
                                </td>
                                <td class="py-2.5 pr-4">
                                    <x-ui.badge :variant="$module['enabled'] ? 'primary' : 'neutral'">
                                        {{ $module['enabled'] ? __('On') : __('Off') }}
                                    </x-ui.badge>
                                </td>
                                <td class="py-2.5">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($module['included_in_subscription'] ?? false)
                                            <x-ui.badge variant="success" size="sm">{{ __('Subscription') }}</x-ui.badge>
                                        @endif
                                        @if ($module['is_trial'] ?? false)
                                            <x-ui.badge variant="warning" size="sm">{{ __('Trial') }}</x-ui.badge>
                                        @endif
                                        @if ($module['is_addon'] ?? false)
                                            <x-ui.badge variant="primary" size="sm">{{ __('Add-on') }}</x-ui.badge>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8">
                                    <x-ui.empty-state-preset variant="modules" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-entity.section>

        <form method="POST" action="{{ route('administration.modules.update') }}" class="mt-6 space-y-6">
            @csrf
            @method('PUT')

            <x-entity.section :title="__('Feature toggles')">
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($overview['feature_toggles'] as $key => $enabled)
                        <x-forms.checkbox
                            name="feature_toggles[{{ $key }}]"
                            value="1"
                            :label="__(ucfirst(str_replace('_', ' ', $key)))"
                            @checked(old('feature_toggles.'.$key, $enabled))
                        />
                    @endforeach
                </div>
            </x-entity.section>

            <x-entity.section :title="__('Workspace visibility')">
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($overview['workspace_visibility'] as $key => $visible)
                        <x-forms.checkbox
                            name="workspace_visibility[{{ $key }}]"
                            value="1"
                            :label="__(ucfirst($key))"
                            @checked(old('workspace_visibility.'.$key, $visible))
                        />
                    @endforeach
                </div>
            </x-entity.section>

            <x-entity.section :title="__('Default landing pages')" :subtitle="__('Route names by role persona')">
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($overview['default_landing_pages'] as $role => $page)
                        <x-forms.field :label="__(ucfirst(str_replace('_', ' ', $role)))" :name="'default_landing_pages.'.$role">
                            <x-forms.input
                                name="default_landing_pages[{{ $role }}]"
                                value="{{ old('default_landing_pages.'.$role, $page) }}"
                            />
                        </x-forms.field>
                    @endforeach
                </div>
            </x-entity.section>

            <x-ui.button type="submit" variant="primary">{{ __('Save preferences') }}</x-ui.button>
        </form>
    </x-layouts.settings>
</x-app-layout>
