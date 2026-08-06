@php
    $selectedChannels = old('channels', $campaign->channels ?? []);
    if (is_string($selectedChannels)) {
        $selectedChannels = array_filter(array_map('trim', explode(',', $selectedChannels)));
    }
    $audience = old('audience', $campaign->audience ?? []);
@endphp

<div class="space-y-8">
    <x-forms.section :title="__('Campaign details')" :subtitle="__('Name, status, and description')">
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Name')" name="name" required>
                <x-forms.input id="name" type="text" name="name" :value="old('name', $campaign->name)" required />
            </x-forms.field>
        </div>

        <x-forms.field :label="__('Status')" name="status" required>
            <x-forms.select id="status" name="status" required>
                @foreach ($statuses as $value)
                    <option value="{{ $value }}" @selected(old('status', $campaign->status) === $value)>{{ __(ucfirst($value)) }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>

        <div class="sm:col-span-2">
            <x-forms.field :label="__('Description')" name="description">
                <x-forms.textarea id="description" name="description" rows="4">{{ old('description', $campaign->description) }}</x-forms.textarea>
            </x-forms.field>
        </div>
    </x-forms.section>

    <x-forms.section :title="__('Budget')" :subtitle="__('Planned spend for this campaign')">
        <x-forms.field :label="__('Budget amount')" name="budget_amount">
            <x-forms.input id="budget_amount" type="number" name="budget_amount" step="0.01" min="0" :value="old('budget_amount', $campaign->budget_amount)" />
        </x-forms.field>

        <x-forms.field :label="__('Budget currency')" name="budget_currency">
            <x-forms.input id="budget_currency" type="text" name="budget_currency" maxlength="3" :value="old('budget_currency', $campaign->budget_currency ?? 'USD')" />
        </x-forms.field>
    </x-forms.section>

    <x-forms.section :title="__('Channels')" :subtitle="__('Where this campaign runs')">
        <div class="sm:col-span-2">
            @if (! empty($channelOptions))
                <fieldset>
                    <legend class="sr-only">{{ __('Channels') }}</legend>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($channelOptions as $option)
                            <label class="flex items-center gap-2 text-sm text-ink">
                                <input
                                    type="checkbox"
                                    name="channels[]"
                                    value="{{ $option }}"
                                    @checked(in_array($option, $selectedChannels, true))
                                    class="rounded border-line text-primary-600 focus:ring-primary-500"
                                >
                                <span>{{ __(ucfirst(str_replace('_', ' ', $option))) }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            <x-forms.field :label="__('Additional channels')" name="channels_text" :hint="__('Comma-separated if not listed above')" class="mt-4">
                <x-forms.input id="channels_text" type="text" name="channels_text" :value="old('channels_text')" placeholder="podcast, affiliate" />
            </x-forms.field>
        </div>
    </x-forms.section>

    <x-forms.section :title="__('Audience')" :subtitle="__('Target segment and notes')">
        <x-forms.field :label="__('Segment')" name="audience[segment]">
            <x-forms.input id="audience-segment" type="text" name="audience[segment]" :value="old('audience.segment', $audience['segment'] ?? '')" />
        </x-forms.field>

        <div class="sm:col-span-2">
            <x-forms.field :label="__('Notes')" name="audience[notes]">
                <x-forms.textarea id="audience-notes" name="audience[notes]" rows="3">{{ old('audience.notes', $audience['notes'] ?? '') }}</x-forms.textarea>
            </x-forms.field>
        </div>
    </x-forms.section>

    <x-forms.section :title="__('Attribution & timeline')" :subtitle="__('UTM tracking and schedule')">
        <x-forms.field :label="__('UTM campaign')" name="utm_campaign">
            <x-forms.input id="utm_campaign" type="text" name="utm_campaign" :value="old('utm_campaign', $campaign->utm_campaign)" />
        </x-forms.field>

        <x-forms.field :label="__('Starts at')" name="starts_at">
            <x-forms.input id="starts_at" type="datetime-local" name="starts_at" :value="old('starts_at', $campaign->starts_at?->format('Y-m-d\TH:i'))" />
        </x-forms.field>

        <x-forms.field :label="__('Ends at')" name="ends_at">
            <x-forms.input id="ends_at" type="datetime-local" name="ends_at" :value="old('ends_at', $campaign->ends_at?->format('Y-m-d\TH:i'))" />
        </x-forms.field>
    </x-forms.section>
</div>
