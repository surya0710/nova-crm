@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <x-ui.page-header :title="__('Validate').': '.$period->name">
            <x-slot:actions>
                <x-ui.button :href="route('hrms.attendance.periods.show', $period)" variant="secondary" size="sm">{{ __('Back') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <p class="mb-4 text-sm">
                {{ $validation['passed'] ? __('Validation passed.') : __('Validation failed with blocking errors.') }}
            </p>

            <h2 class="mb-2 text-sm font-semibold">{{ __('Errors') }}</h2>
            @forelse ($validation['errors'] as $error)
                <div class="mb-2 rounded border border-edge px-3 py-2 text-sm">
                    <code>{{ $error['code'] }}</code> — {{ $error['message'] }}
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('None') }}</p>
            @endforelse

            <h2 class="mb-2 mt-4 text-sm font-semibold">{{ __('Warnings') }}</h2>
            @forelse ($validation['warnings'] as $warning)
                <div class="mb-2 rounded border border-edge px-3 py-2 text-sm text-ink-muted">
                    <code>{{ $warning['code'] }}</code> — {{ $warning['message'] }}
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('None') }}</p>
            @endforelse
        </x-ui.card>
    </div>
@endsection
