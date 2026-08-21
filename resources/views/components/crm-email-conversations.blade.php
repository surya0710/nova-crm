@props(['related'])

@php
    $conversations = app(\App\Services\CrmEmailConversationService::class)->forRelated($related, 8);
@endphp

@if ($conversations->isNotEmpty())
    <x-entity.section :title="__('Email conversations')">
        <x-slot:actions>
            <x-ui.button :href="route('crm.communications.index')" variant="link" size="sm">{{ __('View all') }}</x-ui.button>
        </x-slot:actions>
        <div class="divide-y divide-line">
            @foreach ($conversations as $conversation)
                <a href="{{ route('crm.communications.show', $conversation) }}" class="flex items-start justify-between gap-3 py-3 hover:bg-surface-muted/60">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-ink-heading">{{ $conversation->subject }}</p>
                        <p class="mt-0.5 text-xs text-ink-muted">
                            {{ trans_choice(':count message|:count messages', $conversation->message_count, ['count' => $conversation->message_count]) }}
                            · {{ $conversation->last_message_at?->diffForHumans() }}
                        </p>
                    </div>
                    <x-ui.badge variant="neutral">{{ $conversation->lastStatusLabel() }}</x-ui.badge>
                </a>
            @endforeach
        </div>
    </x-entity.section>
@endif
