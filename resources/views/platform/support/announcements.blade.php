@php $canManage = auth('platform')->user()->hasPermission('platform.support.manage'); @endphp

<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Announcements')"
        :subtitle="__('Maintenance notices, incidents, and broadcasts')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Support'), 'href' => route('platform.support.index')],
                ['label' => __('Announcements'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:filters>
            <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <x-forms.field :label="__('Type')" name="type">
                    <x-forms.select name="type">
                        <option value="">{{ __('All types') }}</option>
                        @foreach (['announcement', 'maintenance', 'incident'] as $type)
                            <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Status')" name="status">
                    <x-forms.select name="status">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (['draft', 'published', 'archived'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end gap-2 sm:col-span-2">
                    <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($canManage)
            <x-ui.card class="mb-4">
                <form method="POST" action="{{ route('platform.support.announcements.store') }}" class="space-y-4">
                    @csrf
                    <h2 class="text-sm font-semibold text-ink-heading">{{ __('Create Announcement') }}</h2>
                    <x-forms.section>
                        <x-forms.field :label="__('Title')" name="title" required class="sm:col-span-2">
                            <x-forms.input name="title" value="{{ old('title') }}" required />
                        </x-forms.field>
                        <x-forms.field :label="__('Type')" name="type">
                            <x-forms.select name="type">
                                @foreach (['announcement', 'maintenance', 'incident'] as $type)
                                    <option value="{{ $type }}" @selected(old('type', 'announcement') === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Status')" name="status">
                            <x-forms.select name="status">
                                @foreach (['draft', 'published', 'archived'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', 'draft') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-forms.field :label="__('Starts At')" name="starts_at">
                            <x-forms.input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" />
                        </x-forms.field>
                        <x-forms.field :label="__('Ends At')" name="ends_at">
                            <x-forms.input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" />
                        </x-forms.field>
                        <x-forms.field :label="__('Body')" name="body" required class="sm:col-span-2">
                            <x-forms.textarea name="body" rows="4" required>{{ old('body') }}</x-forms.textarea>
                        </x-forms.field>
                        <x-forms.field name="broadcast" class="sm:col-span-2">
                            <x-forms.checkbox name="broadcast" value="1" :label="__('Broadcast to all tenants')" @checked(old('broadcast')) />
                        </x-forms.field>
                    </x-forms.section>
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Publish Announcement') }}</x-ui.button>
                </form>
            </x-ui.card>
        @endif

        @if ($announcements->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="generic" :title="__('No announcements')" /></x-ui.card>
        @else
            <div class="space-y-4">
                @foreach ($announcements as $announcement)
                    <x-ui.card>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-ink-heading">{{ $announcement->title }}</h2>
                                <p class="mt-1 text-xs text-ink-muted">
                                    {{ ucfirst($announcement->type) }} · {{ ucfirst($announcement->status) }}
                                    @if ($announcement->author)
                                        · {{ $announcement->author->name }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <x-ui.badge variant="neutral">{{ ucfirst($announcement->status) }}</x-ui.badge>
                                @if ($announcement->broadcast)
                                    <x-ui.badge variant="success">{{ __('Broadcast') }}</x-ui.badge>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3 text-sm text-ink whitespace-pre-wrap">{{ $announcement->body }}</div>
                    </x-ui.card>
                @endforeach
            </div>
            <div class="mt-4">{{ $announcements->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-platform-layout>
