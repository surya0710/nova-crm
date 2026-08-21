<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$conversation->subject"
        :subtitle="$conversation->customer?->display_name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Communications'), 'href' => route('crm.communications.index')],
                ['label' => $conversation->subject, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('crm.communications.index')" variant="secondary" size="sm">{{ __('Back') }}</x-ui.button>
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge variant="neutral">{{ $conversation->lastStatusLabel() }}</x-ui.badge>
            <span class="text-xs text-ink-muted">{{ trans_choice(':count message|:count messages', $conversation->message_count, ['count' => $conversation->message_count]) }}</span>
        </x-slot:tabs>

        <x-entity.section :title="__('Conversation')">
            <div class="space-y-4">
                @forelse ($messages as $message)
                    @include('crm.communications._message', ['message' => $message])
                @empty
                    <p class="py-6 text-center text-sm text-ink-muted">{{ __('No messages in this conversation.') }}</p>
                @endforelse
            </div>
        </x-entity.section>

        <x-slot:aside>
            <x-entity.section :title="__('Details')">
                <x-entity.definition-list>
                    @if ($conversation->customer)
                        <x-entity.definition-item :label="__('Customer')" :span="2">
                            <a href="{{ route('customers.show', $conversation->customer) }}" class="text-primary-600 hover:text-primary-700">{{ $conversation->customer->display_name }}</a>
                        </x-entity.definition-item>
                    @endif
                    @if ($conversation->contact)
                        <x-entity.definition-item :label="__('Contact')" :span="2">
                            <a href="{{ route('contacts.show', $conversation->contact) }}" class="text-primary-600 hover:text-primary-700">{{ $conversation->contact->name }}</a>
                        </x-entity.definition-item>
                    @endif
                    <x-entity.definition-item :label="__('Thread')">{{ $conversation->thread_id }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Last message')">{{ $conversation->last_message_at?->format('M j, Y g:i A') ?? '—' }}</x-entity.definition-item>
                </x-entity.definition-list>
            </x-entity.section>
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-app-layout>
