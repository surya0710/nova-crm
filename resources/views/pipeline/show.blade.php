@php
    $stageColors = [
        'qualification' => 'bg-blue-100 text-blue-800',
        'proposal' => 'bg-violet-100 text-violet-800',
        'negotiation' => 'bg-amber-100 text-amber-800',
        'closed_won' => 'bg-emerald-100 text-emerald-800',
        'closed_lost' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-lg font-semibold text-slate-900">{{ $opportunity->title }}</h1>
                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $stageColors[$opportunity->stage] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ $opportunity->stage_label }}
                    </span>
                </div>
                @if ($opportunity->customer)
                    <p class="text-sm text-slate-500">{{ $opportunity->customer->display_name }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @can('update', $opportunity)
                    <a href="{{ route('pipeline.edit', $opportunity) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">{{ __('Edit') }}</a>
                @endcan
                @can('delete', $opportunity)
                    <form method="POST" action="{{ route('pipeline.destroy', $opportunity) }}" onsubmit="return confirm('{{ __('Delete this deal?') }}')">
                        @csrf @method('DELETE')
                        <x-danger-button type="submit">{{ __('Delete') }}</x-danger-button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <x-flash-messages />

    @can('update', $opportunity)
        @if ($opportunity->isOpen())
            <div class="mb-6 rounded-xl bg-white border border-slate-200 shadow-sm p-5 sm:p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Pipeline Stage') }}</h3>
                        <p class="mt-0.5 text-sm text-slate-500">{{ __('Move this deal through your pipeline.') }}</p>
                    </div>
                    <form method="POST" action="{{ route('pipeline.stage.update', $opportunity) }}" class="flex items-center gap-3">
                        @csrf @method('PATCH')
                        <select name="stage" onchange="this.form.submit()" class="text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm py-2.5 px-3 min-w-[200px]">
                            @foreach (config('pipeline.open_stages') as $value)
                                <option value="{{ $value }}" @selected($opportunity->stage === $value)>{{ config('pipeline.stages.'.$value) }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-medium text-slate-500 self-center mr-1">{{ __('Quick set:') }}</span>
                    @foreach (config('pipeline.open_stages') as $stage)
                        @if ($opportunity->stage !== $stage)
                            <form method="POST" action="{{ route('pipeline.stage.update', $opportunity) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="stage" value="{{ $stage }}">
                                <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition">
                                    {{ config('pipeline.stages.'.$stage) }}
                                </button>
                            </form>
                        @endif
                    @endforeach
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-2">
                    <button
                        type="button"
                        x-data=""
                        x-on:click="$dispatch('open-modal', 'opportunity-mark-won')"
                        class="inline-flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-100 transition"
                    >
                        {{ __('Mark as Won') }}
                    </button>
                    <button
                        type="button"
                        x-data=""
                        x-on:click="$dispatch('open-modal', 'opportunity-mark-lost')"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
                    >
                        {{ __('Mark as Lost') }}
                    </button>
                </div>
            </div>
            <x-opportunity-close-modal :opportunity="$opportunity" />
        @else
            <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-5 sm:p-6">
                <p class="text-sm text-slate-600">
                    {{ __('This deal is closed and cannot be moved to another stage.') }}
                </p>
            </div>
        @endif
    @endcan

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50"><h3 class="font-semibold text-slate-900">{{ __('Deal Details') }}</h3></div>
                <dl class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Value') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">
                            @if ($opportunity->amount){{ $opportunity->currency }} {{ number_format($opportunity->amount, 2) }}@else—@endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Probability') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $opportunity->probability !== null ? $opportunity->probability.'%' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Expected Close') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $opportunity->expected_close_date?->format('M j, Y') ?? '—' }}</dd>
                    </div>
                    @if ($opportunity->isWon())
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Won Date') }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-emerald-700">{{ $opportunity->won_at?->format('M j, Y') ?? '—' }}</dd>
                        </div>
                    @endif
                    @if ($opportunity->isLost())
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Lost Reason') }}</dt>
                            <dd class="mt-1 text-sm text-slate-700 whitespace-pre-wrap">{{ $opportunity->lost_reason ?? '—' }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Assigned To') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $opportunity->assignee?->name ?? __('Unassigned') }}</dd>
                    </div>
                    @if ($opportunity->customer)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Customer') }}</dt>
                            <dd class="mt-1 text-sm"><a href="{{ route('customers.show', $opportunity->customer) }}" class="text-indigo-600 hover:text-indigo-800">{{ $opportunity->customer->display_name }}</a></dd>
                        </div>
                    @endif
                    @if ($opportunity->lead)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Related Lead') }}</dt>
                            <dd class="mt-1 text-sm"><a href="{{ route('leads.show', $opportunity->lead) }}" class="text-indigo-600 hover:text-indigo-800">{{ $opportunity->lead->name }}</a></dd>
                        </div>
                    @endif
                    @if ($opportunity->description)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Description') }}</dt>
                            <dd class="mt-1 text-sm text-slate-700 whitespace-pre-wrap">{{ $opportunity->description }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            @include('metadata-fields._runtime_detail', [
                'metadataFields' => $metadataFields ?? collect(),
                'metadataPresenter' => $metadataPresenter ?? app(\App\Services\MetadataFormValuePresenter::class),
                'record' => $opportunity,
            ])

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50"><h3 class="font-semibold text-slate-900">{{ __('Activity') }}</h3></div>
                <div class="p-6">
                    @can('update', $opportunity)
                        <form method="POST" action="{{ route('pipeline.notes.store', $opportunity) }}" class="mb-6">
                            @csrf
                            <textarea name="body" rows="3" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="{{ __('Add a note…') }}" required>{{ old('body') }}</textarea>
                            <div class="mt-3 flex justify-end"><x-primary-button type="submit">{{ __('Add Note') }}</x-primary-button></div>
                        </form>
                    @endcan
                    @forelse ($opportunity->notes as $note)
                        <div class="flex gap-3 {{ ! $loop->last ? 'mb-4 pb-4 border-b border-slate-100' : '' }}">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold shrink-0">{{ strtoupper(substr($note->user->name, 0, 1)) }}</div>
                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ $note->user->name }} <span class="text-xs text-slate-400 font-normal">{{ $note->created_at->diffForHumans() }}</span></p>
                                <p class="mt-1 text-sm text-slate-600 whitespace-pre-wrap">{{ $note->body }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 text-center py-4">{{ __('No activity yet.') }}</p>
                    @endforelse
                </div>
            </div>

            <x-attachments-panel
                attachable-type="opportunity"
                :attachable-id="$opportunity->id"
                :attachments="$opportunity->attachments"
                :can-upload="auth()->user()->can('attachments.create')"
                :can-delete="auth()->user()->can('attachments.delete')"
            />

            <x-tasks-panel
                taskable-type="opportunity"
                :taskable-id="$opportunity->id"
                :tasks="$opportunity->tasks"
                :can-create="auth()->user()->can('tasks.create')"
            />
        </div>
        <div>
            <a href="{{ route('pipeline.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">← {{ __('Back to pipeline') }}</a>
        </div>
    </div>
</x-app-layout>
