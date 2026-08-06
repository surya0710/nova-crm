@php
    /** @var array $card */
@endphp
<div class="board-card rounded-lg bg-white border border-slate-200 p-3 shadow-sm cursor-grab active:cursor-grabbing relative group"
     draggable="true"
     data-task-id="{{ $card['id'] }}"
     data-url="{{ $card['url'] }}">
    <div class="flex items-start justify-between gap-2">
        <button type="button" data-quick-action="open" data-field="title" class="text-left text-sm font-medium text-slate-900 hover:text-primary-700">
            {{ $card['title'] }}
        </button>
        @if (! empty($card['is_overdue']))
            <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-red-700 bg-red-50 px-1.5 py-0.5 rounded">{{ __('Overdue') }}</span>
        @endif
    </div>

    <div class="mt-2 flex flex-wrap items-center gap-1.5">
        @if (! empty($card['priority']))
            <span class="inline-flex text-[10px] font-medium px-1.5 py-0.5 rounded" style="background: {{ ($card['priority_color'] ?? '#e2e8f0') }}22; color: {{ $card['priority_color'] ?? '#475569' }}">
                {{ $card['priority'] }}
            </span>
        @endif
        @if (! empty($card['has_dependencies']))
            <span class="text-[10px] text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded" title="{{ __('Has dependencies') }}">⛓ {{ $card['dependency_count'] }}</span>
        @endif
        @foreach ($card['labels'] ?? [] as $label)
            <span class="text-[10px] px-1.5 py-0.5 rounded" style="background: {{ ($label['color'] ?? '#cbd5e1') }}33">{{ $label['name'] }}</span>
        @endforeach
    </div>

    <div class="mt-2 flex items-center justify-between gap-2 text-[11px] text-slate-500">
        <div class="flex items-center gap-1.5 min-w-0">
            @if (! empty($card['assignee']))
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-[10px] font-semibold" title="{{ $card['assignee']['name'] }}">
                    {{ $card['assignee']['initials'] }}
                </span>
            @else
                <span class="text-slate-400">{{ __('Unassigned') }}</span>
            @endif
            @if (! empty($card['due_date']))
                <span>{{ \Illuminate\Support\Carbon::parse($card['due_date'])->format('M j') }}</span>
            @endif
        </div>
        <span data-field="progress">{{ $card['completion_percentage'] }}%</span>
    </div>

    <div class="mt-2 flex flex-wrap gap-2 text-[10px] text-slate-500">
        <span>✓ {{ $card['checklist']['done'] }}/{{ $card['checklist']['total'] }}</span>
        <span>⏱ {{ number_format($card['estimated_hours'], 1) }}h / {{ number_format($card['logged_hours'], 1) }}h</span>
        <span>📎 {{ $card['attachment_count'] }}</span>
        <span>💬 {{ $card['comment_count'] }}</span>
    </div>

    <div class="mt-2 hidden group-hover:flex flex-wrap gap-1">
        <button type="button" data-quick-action="status" class="text-[10px] text-primary-600 hover:underline">{{ __('Status') }}</button>
        <button type="button" data-quick-action="assign" class="text-[10px] text-primary-600 hover:underline">{{ __('Assign') }}</button>
        <button type="button" data-quick-action="priority" class="text-[10px] text-primary-600 hover:underline">{{ __('Priority') }}</button>
        <button type="button" data-quick-action="log_time" class="text-[10px] text-primary-600 hover:underline">{{ __('Log time') }}</button>
        <button type="button" data-quick-action="checklist" class="text-[10px] text-primary-600 hover:underline">{{ __('Checklist') }}</button>
        <button type="button" data-quick-action="comment" class="text-[10px] text-primary-600 hover:underline">{{ __('Comment') }}</button>
        <a href="{{ $card['url'] }}" class="text-[10px] text-slate-600 hover:underline">{{ __('Details') }}</a>
    </div>

    <form class="quick-action-form hidden mt-2 space-y-1" data-action="status" data-quick-panel="status">
        <select name="status_id" class="w-full border-gray-300 rounded text-xs">
            @foreach ($statuses as $status)
                <option value="{{ $status->id }}" @selected($card['status_id'] == $status->id)>{{ $status->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="text-xs text-primary-600">{{ __('Update') }}</button>
    </form>
    <form class="quick-action-form hidden mt-2 space-y-1" data-action="assign" data-quick-panel="assign">
        <select name="assigned_to" class="w-full border-gray-300 rounded text-xs">
            <option value="">{{ __('Unassigned') }}</option>
            @foreach ($assignees as $user)
                <option value="{{ $user->id }}" @selected(($card['assignee']['id'] ?? null) == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="text-xs text-primary-600">{{ __('Assign') }}</button>
    </form>
    <form class="quick-action-form hidden mt-2 space-y-1" data-action="priority" data-quick-panel="priority">
        <select name="priority" class="w-full border-gray-300 rounded text-xs">
            @foreach ($priorities as $priority)
                <option value="{{ $priority->slug }}" @selected(($card['priority_slug'] ?? '') === $priority->slug)>{{ $priority->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="text-xs text-primary-600">{{ __('Update') }}</button>
    </form>
    <form class="quick-action-form hidden mt-2 space-y-1" data-action="log_time" data-quick-panel="log_time">
        <input type="number" name="duration_minutes" min="1" max="1440" placeholder="{{ __('Minutes') }}" class="w-full border-gray-300 rounded text-xs" required>
        <button type="submit" class="text-xs text-primary-600">{{ __('Log') }}</button>
    </form>
    <form class="quick-action-form hidden mt-2 space-y-1" data-action="checklist" data-quick-panel="checklist">
        <input type="text" name="title" placeholder="{{ __('Checklist item') }}" class="w-full border-gray-300 rounded text-xs" required>
        <button type="submit" class="text-xs text-primary-600">{{ __('Add') }}</button>
    </form>
    <form class="quick-action-form hidden mt-2 space-y-1" data-action="comment" data-quick-panel="comment">
        <textarea name="body" rows="2" placeholder="{{ __('Comment') }}" class="w-full border-gray-300 rounded text-xs" required></textarea>
        <button type="submit" class="text-xs text-primary-600">{{ __('Post') }}</button>
    </form>
</div>
