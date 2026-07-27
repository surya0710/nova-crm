<form id="onboarding-step-form" method="post" action="{{ route('platform.onboarding.steps', $onboarding) }}" class="mt-6 space-y-4">
    @csrf
    <input type="hidden" name="step" value="providers">

    <x-ui.alert variant="info">
        {{ __('Platform provider health is based on environment credentials. Org-specific OAuth can be finished after go-live.') }}
    </x-ui.alert>

    <div class="overflow-x-auto rounded-lg border border-line">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="bg-surface-muted/40 text-left text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-3 py-2">{{ __('Provider') }}</th>
                    <th class="px-3 py-2">{{ __('Status') }}</th>
                    <th class="px-3 py-2">{{ __('Skip') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($providerHealth['items'] ?? [] as $provider)
                    <tr>
                        <td class="px-3 py-2">{{ $provider['label'] ?? $provider['key'] }}</td>
                        <td class="px-3 py-2">{{ $provider['status'] ?? 'unknown' }}</td>
                        <td class="px-3 py-2">
                            <input type="checkbox" name="skipped_providers[]" value="{{ $provider['key'] }}" class="rounded border-line text-primary-600"
                                @checked(in_array($provider['key'], old('skipped_providers', $stepData['skipped_providers'] ?? []), true))>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <label class="inline-flex items-center gap-2 text-sm">
        <input type="hidden" name="acknowledged" value="0">
        <input type="checkbox" name="acknowledged" value="1" class="rounded border-line text-primary-600" @checked(old('acknowledged', true)) required>
        {{ __('I have reviewed provider health for this go-live') }}
    </label>

    @include('platform.onboarding.partials.actions')
</form>
