@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Name'),
        __('Email'),
        ['label' => __('Source'), 'class' => 'hidden md:table-cell'],
        __('Actions'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Candidates')"
        :subtitle="__('Talent pipeline and applicant profiles')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Candidates'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\Candidate::class)
            <x-ui.card class="mb-6">
                <x-entity.section :title="__('Add candidate')">
                    <form method="POST" action="{{ route('hrms.recruitment.candidates.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        @csrf
                        <x-forms.field :label="__('First name')" name="first_name" class="mb-0">
                            <x-forms.input name="first_name" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Last name')" name="last_name" class="mb-0">
                            <x-forms.input name="last_name" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Email')" name="email" class="mb-0">
                            <x-forms.input name="email" type="email" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Phone')" name="phone" class="mb-0">
                            <x-forms.input name="phone" />
                        </x-forms.field>
                        <x-forms.field :label="__('Current company')" name="current_company" class="mb-0">
                            <x-forms.input name="current_company" />
                        </x-forms.field>
                        <x-forms.field :label="__('Source')" name="source" class="mb-0">
                            <x-forms.select name="source">
                                <option value="">{{ __('Source') }}</option>
                                @foreach ($sources as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <div class="md:col-span-3">
                            <x-forms.field :label="__('Resume')" name="resume" class="mb-0">
                                <input type="file" name="resume" class="block w-full text-sm text-ink" />
                            </x-forms.field>
                        </div>
                        <div class="md:col-span-3">
                            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Candidate') }}</x-ui.button>
                        </div>
                    </form>
                </x-entity.section>
            </x-ui.card>
        @endcan

        @if ($candidates->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="candidates" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($candidates as $candidate)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $candidate->fullName() }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $candidate->email }}</td>
                        <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-muted">{{ $candidate->sourceLabel() }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('hrms.recruitment.candidates.show', $candidate) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('View') }}</a>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
            <div class="mt-4">{{ $candidates->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
