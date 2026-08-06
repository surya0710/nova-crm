<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Resume Parsing')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Resume Parsing'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @php
        $requests = $requests ?? collect();
        $candidates = $candidates ?? collect();
    @endphp

    <div class="mb-4">
        <a href="{{ route('hrms.recruitment.integrations.index') }}" class="text-sm text-indigo-600">{{ __('← Integrations') }}</a>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium mb-3">{{ __('Request Parse') }}</h2>
        <form method="POST" action="{{ route('hrms.recruitment.resume-parsing.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            @if ($candidates->isNotEmpty())
                <select name="candidate_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">{{ __('Candidate (optional)') }}</option>
                    @foreach ($candidates as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->fullName() }} (#{{ $candidate->id }})</option>
                    @endforeach
                </select>
            @else
                <x-forms.input name="candidate_id" type="number" placeholder="{{ __('Candidate ID (optional)') }}"  />
            @endif
            <x-forms.input name="filename" placeholder="{{ __('Filename') }}"  />
            <x-forms.input name="mime_type" placeholder="{{ __('MIME type') }}"  />
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Parse Resume') }}</x-ui.button>
        </form>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('ID') }}</th>
                    <th class="p-3 text-left">{{ __('Candidate') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Applied') }}</th>
                    <th class="p-3 text-left">{{ __('Last Error') }}</th>
                    <th class="p-3 text-left">{{ __('Apply') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($requests as $parseRequest)
                <tr class="border-t">
                    <td class="p-3">{{ $parseRequest->id }}</td>
                    <td class="p-3">{{ $parseRequest->candidate_id ?? '—' }}</td>
                    <td class="p-3">{{ $parseRequest->status }}</td>
                    <td class="p-3">{{ $parseRequest->applied_to_candidate ? __('Yes') : __('No') }}</td>
                    <td class="p-3 text-rose-600 max-w-xs truncate">{{ $parseRequest->last_error ?? '—' }}</td>
                    <td class="p-3">
                        @if (! $parseRequest->applied_to_candidate)
                            <form method="POST" action="{{ route('hrms.recruitment.resume-parsing.apply', $parseRequest) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                @if ($candidates->isNotEmpty())
                                    <select name="candidate_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                                        <option value="">{{ __('Candidate') }}</option>
                                        @foreach ($candidates as $candidate)
                                            <option value="{{ $candidate->id }}" @selected((int) $parseRequest->candidate_id === (int) $candidate->id)>
                                                {{ $candidate->fullName() }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <x-forms.input name="candidate_id" type="number" placeholder="{{ __('Candidate ID') }}" value="{{ $parseRequest->candidate_id }}" required class="w-28" />
                                @endif
                                <label class="inline-flex items-center gap-1 text-xs text-slate-600">
                                    <input type="checkbox" name="overwrite_confirmed" value="1" class="rounded border-slate-300">
                                    {{ __('Overwrite') }}
                                </label>
                                <button type="submit" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('Apply') }}</button>
                            </form>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="border-t"><td class="p-3 text-slate-500" colspan="6">{{ __('No parse requests yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        @if (method_exists($requests, 'links'))
            <div class="p-4">{{ $requests->links() }}</div>
        @endif
    </div>
    </x-layouts.settings>
</x-app-layout>
