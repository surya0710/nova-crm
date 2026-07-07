<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Notifications') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Updates and assignments') }}</p>
            </div>
            @if ($notifications->total() > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-secondary-button type="submit">{{ __('Mark all read') }}</x-secondary-button>
                </form>
            @endif
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($notifications->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No notifications yet.') }}</div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($notifications as $notification)
                    @php $data = $notification->data; @endphp
                    <div class="px-6 py-4 {{ $notification->read_at ? 'bg-white' : 'bg-indigo-50/40' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $data['title'] ?? __('Notification') }}</p>
                                <p class="text-sm text-slate-600 mt-1">{{ $data['message'] ?? '' }}</p>
                                <p class="text-xs text-slate-400 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            @if (! $notification->read_at && ! empty($data['action_url']))
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <x-primary-button type="submit" class="text-xs">{{ __('View') }}</x-primary-button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @if ($notifications->hasPages())
                <div class="px-6 py-4 border-t">{{ $notifications->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
