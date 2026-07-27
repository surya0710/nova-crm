<form id="onboarding-step-form" method="post" action="{{ route('platform.onboarding.steps', $onboarding) }}" class="mt-6 space-y-4">
    @csrf
    <input type="hidden" name="step" value="imports">

    <x-ui.alert variant="info">
        {{ __('Select entities to prepare. After go-live, open Import Center while impersonating the organization admin to upload files. Progress from existing import sessions is shown below.') }}
    </x-ui.alert>

    @foreach ($importEntities as $module => $entities)
        <div>
            <p class="mb-2 text-sm font-medium text-ink-heading">{{ ucfirst($module) }}</p>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($entities as $entity)
                    <label class="flex items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm">
                        <input type="checkbox" name="entities[]" value="{{ $entity }}" class="rounded border-line text-primary-600"
                            @checked(in_array($entity, old('entities', $stepData['entities'] ?? []), true))>
                        <span>{{ ucfirst(str_replace('_', ' ', $entity)) }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach

    @if (! empty($stepData['recent_sessions']))
        <div class="rounded-lg border border-line p-3 text-sm">
            <p class="font-medium text-ink-heading">{{ __('Recent import sessions') }}</p>
            <ul class="mt-2 space-y-1 text-ink-muted">
                @foreach ($stepData['recent_sessions'] as $session)
                    <li>{{ $session['entity_type'] ?? '' }} · {{ $session['status'] ?? '' }} · #{{ $session['id'] ?? '' }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <label class="inline-flex items-center gap-2 text-sm">
        <input type="hidden" name="deferred" value="0">
        <input type="checkbox" name="deferred" value="1" class="rounded border-line text-primary-600" @checked(old('deferred', $stepData['deferred'] ?? true))>
        {{ __('Defer file upload (continue wizard)') }}
    </label>

    @include('platform.onboarding.partials.actions')
</form>
