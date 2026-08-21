@php
    $statusVariant = match ($message->status) {
        'failed', 'bounced' => 'danger',
        'delivered' => 'success',
        'queued', 'sending' => 'warning',
        default => 'neutral',
    };
@endphp
<article class="rounded-xl border border-line bg-surface-card p-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-medium text-ink-heading">{{ $message->subject }}</p>
            <p class="mt-1 text-xs text-ink-muted">
                {{ $message->from_name ?: $message->sender?->name ?: __('System') }}
                @if ($message->from_email)
                    &lt;{{ $message->from_email }}&gt;
                @endif
                · {{ $message->created_at?->format('M j, Y g:i A') }}
            </p>
        </div>
        <x-ui.badge :variant="$statusVariant">{{ $message->statusLabel() }}</x-ui.badge>
    </div>

    <dl class="mt-3 grid grid-cols-1 gap-1 text-xs text-ink-muted sm:grid-cols-2">
        <div><span class="font-medium text-ink-heading">{{ __('To') }}:</span> {{ implode(', ', $message->to ?? []) }}</div>
        @if ($message->cc)
            <div><span class="font-medium text-ink-heading">{{ __('CC') }}:</span> {{ implode(', ', $message->cc) }}</div>
        @endif
        @if ($message->provider)
            <div><span class="font-medium text-ink-heading">{{ __('Provider') }}:</span> {{ $message->provider }}</div>
        @endif
        @if ($message->rfc_message_id)
            <div class="sm:col-span-2"><span class="font-medium text-ink-heading">{{ __('Message-ID') }}:</span> {{ $message->rfc_message_id }}</div>
        @endif
    </dl>

    @if ($message->body)
        <div class="mt-3 whitespace-pre-line text-sm text-ink">{{ $message->body }}</div>
    @endif

    @if ($message->attachments)
        <ul class="mt-3 list-disc pl-5 text-xs text-ink-muted">
            @foreach ($message->attachments as $name)
                <li>{{ $name }}</li>
            @endforeach
        </ul>
    @endif

    @if ($message->error_message || $message->bounce_reason)
        <p class="mt-3 text-xs text-red-700">{{ $message->bounce_reason ?: $message->error_message }}</p>
    @endif
</article>
