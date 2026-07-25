<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Platform Audit Log')"
        :subtitle="__('Search platform and tenant administrative activity')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Audit Log'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:filters>
            <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <x-forms.field :label="__('Search')" name="search" class="sm:col-span-2">
                    <x-forms.input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Event, subject, IP…') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Category')" name="category">
                    <x-forms.select name="category">
                        <option value="">{{ __('All categories') }}</option>
                        @foreach (['security' => __('Security'), 'organization' => __('Organization'), 'administrative' => __('Administrative')] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Organization')" name="organization_id">
                    <x-forms.select name="organization_id">
                        <option value="">{{ __('All organizations') }}</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}" @selected((string) ($filters['organization_id'] ?? '') === (string) $organization->id)>{{ $organization->name }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('From')" name="from">
                    <x-forms.input type="date" name="from" value="{{ $filters['from'] ?? '' }}" />
                </x-forms.field>
                <x-forms.field :label="__('To')" name="to">
                    <x-forms.input type="date" name="to" value="{{ $filters['to'] ?? '' }}" />
                </x-forms.field>
                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6">
                    <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Search') }}</x-ui.button>
                    <x-ui.button :href="route('platform.audit.index')" variant="ghost" size="sm">{{ __('Reset') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($logs->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="platform_audit" /></x-ui.card>
        @else
            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('When') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Event') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Actor') }}</th>
                                <th scope="col" class="hidden px-4 py-3 text-left font-medium md:table-cell">{{ __('Organization') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Subject') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($logs as $log)
                                <tr class="hover:bg-surface-muted/60">
                                    <td class="px-4 py-3 text-ink-muted">{{ $log->created_at->format('M j, Y H:i') }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $log->event }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $log->platformUser?->name ?? '—' }}</td>
                                    <td class="hidden px-4 py-3 text-ink-muted md:table-cell">{{ $log->organization?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $log->subject ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
            <div class="mt-4">{{ $logs->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-platform-layout>
