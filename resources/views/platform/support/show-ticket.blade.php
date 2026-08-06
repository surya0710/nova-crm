@php $canManage = auth('platform')->user()->hasPermission('platform.support.manage'); @endphp

<x-platform-layout>
    <x-layouts.entity-detail
        :title="$ticket->subject"
        :subtitle="__('Support ticket #:id', ['id' => $ticket->id])"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Support'), 'href' => route('platform.support.index')],
                ['label' => __('Tickets'), 'href' => route('platform.support.tickets')],
                ['label' => \Illuminate\Support\Str::limit($ticket->subject, 40), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-entity.section :title="__('Details')">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Organization') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $ticket->organization?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Created By') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $ticket->creator?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Requester') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $ticket->requester_name ?? '—' }} @if($ticket->requester_email)<span class="block text-xs text-ink-muted">{{ $ticket->requester_email }}</span>@endif</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Category') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $ticket->category ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Status') }}</dt>
                    <dd class="mt-1"><x-ui.badge variant="neutral">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</x-ui.badge></dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-muted">{{ __('Priority') }}</dt>
                    <dd class="mt-1"><x-ui.badge :variant="$ticket->priority === 'urgent' || $ticket->priority === 'high' ? 'warning' : 'neutral'">{{ ucfirst($ticket->priority) }}</x-ui.badge></dd>
                </div>
            </dl>
            @if ($ticket->body)
                <div class="mt-4 rounded-lg border border-line bg-surface-muted/30 p-4 text-sm text-ink whitespace-pre-wrap">{{ $ticket->body }}</div>
            @endif
        </x-entity.section>

        @if ($canManage)
            <x-entity.section :title="__('Update Ticket')">
                <form method="POST" action="{{ route('platform.support.tickets.update', $ticket) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <x-forms.section>
                        <x-forms.field :label="__('Subject')" name="subject" required class="sm:col-span-2">
                            <x-forms.input name="subject" value="{{ old('subject', $ticket->subject) }}" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Assignee')" name="assignee_id">
                            <x-forms.select name="assignee_id">
                                <option value="">{{ __('Unassigned') }}</option>
                                @foreach ($assignees as $assignee)
                                    <option value="{{ $assignee->id }}" @selected((int) old('assignee_id', $ticket->assignee_id) === $assignee->id)>{{ $assignee->name }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Status')" name="status">
                            <x-forms.select name="status">
                                @foreach (['open', 'in_progress', 'resolved', 'closed'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $ticket->status) === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Priority')" name="priority">
                            <x-forms.select name="priority">
                                @foreach (['low', 'normal', 'high', 'urgent'] as $priority)
                                    <option value="{{ $priority }}" @selected(old('priority', $ticket->priority) === $priority)>{{ ucfirst($priority) }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Category')" name="category">
                            <x-forms.input name="category" value="{{ old('category', $ticket->category) }}" />
                        </x-forms.field>
                        <x-forms.field :label="__('Body')" name="body" class="sm:col-span-2">
                            <x-forms.textarea name="body" rows="5">{{ old('body', $ticket->body) }}</x-forms.textarea>
                        </x-forms.field>
                    </x-forms.section>

                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save Changes') }}</x-ui.button>
                </form>
            </x-entity.section>
        @endif

        <x-slot:aside>
            <x-entity.section :title="__('Timeline')">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Created') }}</dt>
                        <dd class="text-ink">{{ $ticket->created_at->format('M j, Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Updated') }}</dt>
                        <dd class="text-ink">{{ $ticket->updated_at->diffForHumans() }}</dd>
                    </div>
                    @if ($ticket->resolved_at)
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Resolved') }}</dt>
                            <dd class="text-ink">{{ $ticket->resolved_at->format('M j, Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </x-entity.section>
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-platform-layout>
