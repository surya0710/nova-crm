@php $canManage = auth('platform')->user()->hasPermission('platform.licensing.manage'); @endphp

<x-platform-layout>
    <x-layouts.settings
        :title="__('Licensing')"
        :subtitle="__('Plan definitions, modules, limits, and quotas')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Licensing'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @if (empty($plans))
            <x-ui.card><x-ui.empty-state-preset variant="plans" /></x-ui.card>
        @else
            <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                @foreach ($plans as $slug => $plan)
                    <x-ui.card>
                        <x-slot:header>
                            <div>
                                <h2 class="text-sm font-semibold text-ink-heading">{{ $plan['name'] ?? $slug }}</h2>
                                <p class="mt-1 text-xs text-ink-muted">{{ $plan['description'] ?? '' }}</p>
                            </div>
                        </x-slot:header>

                        <dl class="mb-4 space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-ink-muted">{{ __('Users') }}</dt>
                                <dd class="text-ink">{{ $plan['limits']['users'] ?? __('Unlimited') }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-ink-muted">{{ __('Storage (MB)') }}</dt>
                                <dd class="text-ink">{{ $plan['limits']['storage_mb'] ?? __('Unlimited') }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-ink-muted">{{ __('API / day') }}</dt>
                                <dd class="text-ink">{{ $plan['limits']['api_requests_per_day'] ?? __('Unlimited') }}</dd>
                            </div>
                        </dl>

                        @if (! empty($plan['features']))
                            <div class="mb-4 flex flex-wrap gap-1">
                                @foreach ($plan['features'] as $feature => $enabled)
                                    @if ($enabled)
                                        <x-ui.badge variant="success" size="sm">{{ $feature }}</x-ui.badge>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        @if ($canManage)
                            <form method="POST" action="{{ route('platform.licensing.update-plan') }}" class="space-y-3 border-t border-line pt-4">
                                @csrf
                                <input type="hidden" name="slug" value="{{ $slug }}">
                                <x-forms.field :label="__('Display Name')" name="name">
                                    <x-forms.input name="name" value="{{ $plan['name'] ?? $slug }}" />
                                </x-forms.field>
                                <x-forms.field :label="__('Description')" name="description">
                                    <x-forms.textarea name="description" rows="2">{{ $plan['description'] ?? '' }}</x-forms.textarea>
                                </x-forms.field>
                                <x-forms.field :label="__('User Limit')" name="limits[users]">
                                    <x-forms.input type="number" name="limits[users]" value="{{ $plan['limits']['users'] ?? '' }}" min="0" />
                                </x-forms.field>
                                <x-forms.field :label="__('Storage MB')" name="limits[storage_mb]">
                                    <x-forms.input type="number" name="limits[storage_mb]" value="{{ $plan['limits']['storage_mb'] ?? '' }}" min="0" />
                                </x-forms.field>
                                <x-forms.field :label="__('API Requests / Day')" name="limits[api_requests_per_day]">
                                    <x-forms.input type="number" name="limits[api_requests_per_day]" value="{{ $plan['limits']['api_requests_per_day'] ?? '' }}" min="0" />
                                </x-forms.field>
                                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Update Plan') }}</x-ui.button>
                            </form>
                        @endif
                    </x-ui.card>
                @endforeach
            </div>
        @endif

        <x-entity.section :title="__('Available Modules')" class="mt-6">
            @if (empty($modules))
                <p class="text-sm text-ink-muted">{{ __('No modules configured.') }}</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($modules as $moduleKey => $moduleLabel)
                        <x-ui.badge variant="neutral">{{ is_string($moduleLabel) ? $moduleLabel : $moduleKey }}</x-ui.badge>
                    @endforeach
                </div>
            @endif
        </x-entity.section>

        @if ($canManage)
            @php
                $organizationOptions = \App\Models\Organization::query()->orderBy('name')->limit(200)->get(['id', 'name']);
            @endphp

            <x-entity.section :title="__('Organization Module Assignment')" :subtitle="__('Assign enabled modules to a specific organization.')" class="mt-6">
                <form method="POST" action="" id="assign-modules-form" class="space-y-4">
                    @csrf
                    <x-forms.field :label="__('Organization')" name="organization_id">
                        <x-forms.select id="assign-modules-org" required onchange="document.getElementById('assign-modules-form').action = @js(url('/platform/organizations')) + '/' + this.value + '/licensing/modules'">
                            <option value="">{{ __('Select organization…') }}</option>
                            @foreach ($organizationOptions as $organization)
                                <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($modules as $moduleKey => $moduleLabel)
                            <x-forms.checkbox name="modules[]" :value="$moduleKey" :label="is_string($moduleLabel) ? $moduleLabel : $moduleKey" />
                        @endforeach
                    </div>
                    <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Assign Modules') }}</x-ui.button>
                </form>
            </x-entity.section>

            <x-entity.section :title="__('Organization Quotas')" :subtitle="__('Override usage limits for a specific organization.')" class="mt-6">
                <form method="POST" action="" id="set-quotas-form" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @csrf
                    <x-forms.field :label="__('Organization')" name="organization_id" class="sm:col-span-2">
                        <x-forms.select required onchange="document.getElementById('set-quotas-form').action = @js(url('/platform/organizations')) + '/' + this.value + '/licensing/quotas'">
                            <option value="">{{ __('Select organization…') }}</option>
                            @foreach ($organizationOptions as $organization)
                                <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Users')" name="users">
                        <x-forms.input type="number" name="users" min="0" />
                    </x-forms.field>
                    <x-forms.field :label="__('Storage MB')" name="storage_mb">
                        <x-forms.input type="number" name="storage_mb" min="0" />
                    </x-forms.field>
                    <x-forms.field :label="__('API Requests / Day')" name="api_requests_per_day" class="sm:col-span-2">
                        <x-forms.input type="number" name="api_requests_per_day" min="0" />
                    </x-forms.field>
                    <div class="sm:col-span-2">
                        <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Set Quotas') }}</x-ui.button>
                    </div>
                </form>
            </x-entity.section>
        @endif
    </x-layouts.settings>
</x-platform-layout>
