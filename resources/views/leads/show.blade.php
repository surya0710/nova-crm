@php
    $statusColors = [
        'new' => 'bg-blue-100 text-blue-800',
        'contacted' => 'bg-cyan-100 text-cyan-800',
        'qualified' => 'bg-indigo-100 text-indigo-800',
        'proposal_sent' => 'bg-violet-100 text-violet-800',
        'negotiation' => 'bg-amber-100 text-amber-800',
        'won' => 'bg-emerald-100 text-emerald-800',
        'lost' => 'bg-slate-100 text-slate-600',
    ];
    $priorityColors = [
        'low' => 'bg-slate-100 text-slate-600',
        'medium' => 'bg-amber-100 text-amber-800',
        'high' => 'bg-red-100 text-red-800',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-lg font-semibold text-slate-900">{{ $lead->name }}</h1>
                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColors[$lead->status] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ $lead->status_label }}
                    </span>
                </div>
                @if ($lead->company)
                    <p class="text-sm text-slate-500">{{ $lead->company }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @can('update', $lead)
                    <a href="{{ route('leads.edit', $lead) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        {{ __('Edit') }}
                    </a>
                @endcan
                @can('delete', $lead)
                    <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('{{ __('Delete this lead?') }}')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">{{ __('Delete') }}</x-danger-button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Lead details --}}
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Lead Details') }}</h3>
                </div>
                <dl class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Email') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $lead->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Phone') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $lead->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Source') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $lead->source_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Industry') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $lead->industry ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Priority') }}</dt>
                        <dd class="mt-1">
                            <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $priorityColors[$lead->priority] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $lead->priority_label }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Budget') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $lead->budget ? number_format($lead->budget, 2) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Assigned To') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $lead->assignee?->name ?? __('Unassigned') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Created By') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $lead->creator?->name ?? '—' }}</dd>
                    </div>
                    @if ($lead->tags)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Tags') }}</dt>
                            <dd class="mt-2 flex flex-wrap gap-2">
                                @foreach ($lead->tags as $tag)
                                    <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-md">{{ $tag }}</span>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Activity timeline --}}
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Activity') }}</h3>
                    <p class="text-sm text-slate-500 mt-0.5">{{ __('Notes and follow-ups') }}</p>
                </div>
                <div class="p-6">
                    @can('update', $lead)
                        <div class="mb-6">
                            <x-input-label for="body" :value="__('Add a note')" />
                            <form id="lead-note-form" method="POST" action="{{ route('leads.notes.store', $lead) }}">
                                @csrf
                            </form>
                            <textarea
                                id="body"
                                name="body"
                                form="lead-note-form"
                                rows="3"
                                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                placeholder="{{ __('Follow-up call scheduled, sent proposal…') }}"
                                required
                            >{{ old('body') }}</textarea>
                            <x-input-error :messages="$errors->get('body')" class="mt-2" />

                            <div class="mt-3 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                                <form method="POST" action="{{ route('leads.status.update', $lead) }}" class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <label for="lead-status" class="text-xs font-semibold uppercase tracking-wide text-slate-500 shrink-0">{{ __('Status') }}</label>
                                    <select
                                        id="lead-status"
                                        name="status"
                                        onchange="this.form.submit()"
                                        class="text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm py-2 px-3 min-w-[160px]"
                                    >
                                        @foreach (config('leads.statuses') as $value => $label)
                                            <option value="{{ $value }}" @selected($lead->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('status')" />
                                </form>

                                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                    @foreach ([
                                        'contacted' => ['label' => __('Contacted'), 'class' => 'border-cyan-200 text-cyan-700 hover:bg-cyan-50'],
                                        'qualified' => ['label' => __('Qualified'), 'class' => 'border-indigo-200 text-indigo-700 hover:bg-indigo-50'],
                                        'won' => ['label' => __('Won'), 'class' => 'border-emerald-200 text-emerald-700 hover:bg-emerald-50'],
                                        'lost' => ['label' => __('Lost'), 'class' => 'border-slate-300 text-slate-600 hover:bg-slate-50'],
                                    ] as $status => $meta)
                                        @if ($lead->status !== $status)
                                            <form method="POST" action="{{ route('leads.status.update', $lead) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $status }}">
                                                <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded-lg border transition {{ $meta['class'] }}">
                                                    {{ $meta['label'] }}
                                                </button>
                                            </form>
                                        @endif
                                    @endforeach

                                    <x-primary-button type="submit" form="lead-note-form">{{ __('Add Note') }}</x-primary-button>
                                </div>
                            </div>
                        </div>
                    @endcan

                    @if ($lead->notes->isEmpty())
                        <p class="text-sm text-slate-500 text-center py-6">{{ __('No activity yet.') }}</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($lead->notes as $note)
                                <div class="flex gap-3">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold shrink-0">
                                        {{ strtoupper(substr($note->user->name, 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-baseline gap-2 flex-wrap">
                                            <span class="text-sm font-medium text-slate-900">{{ $note->user->name }}</span>
                                            <span class="text-xs text-slate-400">{{ $note->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="mt-1 text-sm text-slate-600 whitespace-pre-wrap">{{ $note->body }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <x-attachments-panel
                attachable-type="lead"
                :attachable-id="$lead->id"
                :attachments="$lead->attachments"
                :can-upload="auth()->user()->can('attachments.create')"
                :can-delete="auth()->user()->can('attachments.delete')"
            />

            <x-tasks-panel
                taskable-type="lead"
                :taskable-id="$lead->id"
                :tasks="$lead->tasks"
                :can-create="auth()->user()->can('tasks.create')"
            />
        </div>

        {{-- Sidebar meta --}}
        <div class="space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Next Follow-up') }}</h3>
                </div>
                <div class="p-6">
                    @if ($lead->hasFollowUpScheduled())
                        <dl class="space-y-3 text-sm mb-4">
                            <div>
                                <dt class="text-xs text-slate-500">{{ __('Scheduled') }}</dt>
                                <dd class="mt-1 font-medium {{ $lead->isFollowUpDue() ? 'text-amber-600' : 'text-slate-900' }}">
                                    {{ $lead->next_follow_up_at->format('M j, Y g:i A') }}
                                    @if ($lead->isFollowUpDue())
                                        <span class="ml-1 text-xs font-semibold uppercase text-amber-600">({{ __('Due now') }})</span>
                                    @endif
                                </dd>
                            </div>
                            @if ($lead->next_follow_up_note)
                                <div>
                                    <dt class="text-xs text-slate-500">{{ __('Notes') }}</dt>
                                    <dd class="mt-1 text-slate-700 whitespace-pre-wrap">{{ $lead->next_follow_up_note }}</dd>
                                </div>
                            @endif
                        </dl>
                    @else
                        <p class="text-sm text-slate-500 mb-4">{{ __('No follow-up scheduled.') }}</p>
                    @endif

                    @can('update', $lead)
                        <form method="POST" action="{{ route('leads.follow-up.update', $lead) }}" class="space-y-3 border-t border-slate-100 pt-4">
                            @csrf
                            @method('PATCH')
                            <div>
                                <x-input-label for="next_follow_up_at" :value="__('Date & Time')" />
                                <x-text-input id="next_follow_up_at" name="next_follow_up_at" type="datetime-local" class="block mt-1 w-full" :value="old('next_follow_up_at', $lead->next_follow_up_at?->format('Y-m-d\TH:i'))" />
                                <x-input-error :messages="$errors->get('next_follow_up_at')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="next_follow_up_note" :value="__('Notes')" />
                                <textarea id="next_follow_up_note" name="next_follow_up_note" rows="2" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" placeholder="{{ __('What to discuss on the call…') }}">{{ old('next_follow_up_note', $lead->next_follow_up_note) }}</textarea>
                                <x-input-error :messages="$errors->get('next_follow_up_note')" class="mt-2" />
                            </div>
                            <x-primary-button type="submit">{{ __('Save Follow-up') }}</x-primary-button>
                        </form>
                    @endcan
                </div>
            </div>

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Timeline') }}</h3>
                <dl class="mt-4 space-y-3">
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Created') }}</dt>
                        <dd class="text-sm text-slate-900">{{ $lead->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ __('Last updated') }}</dt>
                        <dd class="text-sm text-slate-900">{{ $lead->updated_at->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>
            </div>

            <a href="{{ route('leads.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">
                ← {{ __('Back to leads') }}
            </a>
        </div>
    </div>
</x-app-layout>
