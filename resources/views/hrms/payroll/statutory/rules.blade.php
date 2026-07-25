@php
    $density = $shellNav['density'] ?? 'comfortable';
    $versionColumns = [__('Version'), __('Effective From'), __('Effective Until'), __('Status'), __('Actions')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Statutory Rule Sets')" :subtitle="__('Configure jurisdiction-specific statutory rule packs')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Payroll'), 'href' => route('hrms.payroll.index')],
                ['label' => __('Statutory Compliance'), 'href' => route('hrms.payroll.statutory.index')],
                ['label' => __('Rule Sets'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\StatutoryRuleSet::class)
            <x-ui.card class="mb-4">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-ink-heading">{{ __('Create Rule Set') }}</h2>
                    <form method="POST" action="{{ route('hrms.payroll.statutory.rules.seed-india') }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Seed India 2026 Pack') }}</x-ui.button>
                    </form>
                </div>
                <form method="POST" action="{{ route('hrms.payroll.statutory.rules.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    @csrf
                    <x-forms.field :label="__('Code')" name="code">
                        <x-forms.input type="text" name="code" :value="old('code')" required maxlength="60" />
                    </x-forms.field>
                    <x-forms.field :label="__('Name')" name="name">
                        <x-forms.input type="text" name="name" :value="old('name')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Jurisdiction')" name="jurisdiction">
                        <x-forms.select name="jurisdiction">
                            @foreach ($jurisdictions as $code => $label)
                                <option value="{{ $code }}" @selected(old('jurisdiction', 'IN') === $code)>{{ $label }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Version')" name="version">
                        <x-forms.input type="text" name="version" :value="old('version', '1.0')" />
                    </x-forms.field>
                    <x-forms.field :label="__('Effective From')" name="effective_from">
                        <x-forms.input type="date" name="effective_from" :value="old('effective_from', now()->toDateString())" />
                    </x-forms.field>
                    <label class="flex items-center gap-2 pt-6 text-sm text-ink-heading">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-line">
                        {{ __('Activate') }}
                    </label>
                    <x-forms.field :label="__('Description')" name="description" class="md:col-span-3">
                        <textarea name="description" rows="2" class="w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ old('description') }}</textarea>
                    </x-forms.field>
                    <div class="md:col-span-3">
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Rule Set') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endcan

        @if ($ruleSets->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="payroll" :description="__('No rule sets configured. Seed the India 2026 pack to get started.')" />
            </x-ui.card>
        @else
            <div class="space-y-4">
                @foreach ($ruleSets as $ruleSet)
                    <x-entity.section :title="$ruleSet->name . ' (' . $ruleSet->code . ')'" :subtitle="$ruleSet->jurisdiction . ' · ' . ($ruleSet->is_active ? __('Active') : __('Inactive'))">
                        <x-slot:actions>
                            @can('activate', $ruleSet)
                                @unless ($ruleSet->is_active)
                                    <form method="POST" action="{{ route('hrms.payroll.statutory.rules.activate', $ruleSet) }}">
                                        @csrf
                                        <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Activate') }}</x-ui.button>
                                    </form>
                                @endunless
                            @endcan
                        </x-slot:actions>

                        <x-tables.table :columns="$versionColumns" :dense="$density === 'compact'">
                            @foreach ($ruleSet->versions as $version)
                                <tr class="hover:bg-surface-muted/60 transition">
                                    <td class="px-4 py-3 text-sm text-ink-heading">{{ $version->version }}</td>
                                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $version->effective_from?->toDateString() }}</td>
                                    <td class="px-4 py-3 text-sm text-ink-muted">{{ $version->effective_until?->toDateString() ?: '—' }}</td>
                                    <td class="px-4 py-3">
                                        <x-ui.badge variant="neutral">{{ $version->is_active ? __('Active') : __('Inactive') }}</x-ui.badge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-ui.button :href="route('hrms.payroll.statutory.rules.versions.show', [$ruleSet, $version])" variant="link" size="sm">{{ __('View') }}</x-ui.button>
                                    </td>
                                </tr>
                            @endforeach
                        </x-tables.table>

                        @can('update', $ruleSet)
                            <div class="mt-4 border-t border-line pt-4">
                                <h3 class="mb-2 text-sm font-medium text-ink-heading">{{ __('Add Version') }}</h3>
                                <form method="POST" action="{{ route('hrms.payroll.statutory.rules.versions.store', $ruleSet) }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                    @csrf
                                    <x-forms.input type="text" name="version" placeholder="{{ __('Version') }}" required />
                                    <x-forms.input type="date" name="effective_from" required />
                                    <x-forms.input type="date" name="effective_until" />
                                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add') }}</x-ui.button>
                                    <div class="md:col-span-4">
                                        <x-forms.field :label="__('Configuration JSON (optional — defaults to India pack)')" name="configuration_json">
                                            <textarea name="configuration_json" rows="4" class="w-full rounded-md border-line font-mono text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{}"></textarea>
                                        </x-forms.field>
                                    </div>
                                </form>
                            </div>
                        @endcan
                    </x-entity.section>
                @endforeach
            </div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
