@php
    $tagsValue = old('tags', isset($lead->tags) ? implode(', ', $lead->tags ?? []) : '');
@endphp

<div class="space-y-8">
    <x-forms.section :title="__('Contact')" :subtitle="__('Primary person and company')">
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Contact Name')" name="name" required>
                <x-forms.input id="name" type="text" name="name" :value="old('name', $lead->name)" required />
            </x-forms.field>
        </div>

        <x-forms.field :label="__('Company')" name="company">
            <x-forms.input id="company" type="text" name="company" :value="old('company', $lead->company)" />
        </x-forms.field>

        <x-forms.field :label="__('Industry')" name="industry">
            <x-forms.input id="industry" type="text" name="industry" :value="old('industry', $lead->industry)" />
        </x-forms.field>

        <x-forms.field :label="__('Email')" name="email">
            <x-forms.input id="email" type="email" name="email" :value="old('email', $lead->email)" />
        </x-forms.field>

        <x-forms.field :label="__('Phone')" name="phone">
            <x-forms.input id="phone" type="text" name="phone" :value="old('phone', $lead->phone)" />
        </x-forms.field>
    </x-forms.section>

    <x-forms.section :title="__('Address Information')">
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Address')" name="address_line_1">
                <x-forms.input id="address_line_1" type="text" name="address_line_1" :value="old('address_line_1', $lead->address_line_1)" />
            </x-forms.field>
        </div>
        <x-forms.field :label="__('City')" name="city">
            <x-forms.input id="city" type="text" name="city" :value="old('city', $lead->city)" />
        </x-forms.field>
        <x-forms.field :label="__('State')" name="state">
            <x-forms.input id="state" type="text" name="state" :value="old('state', $lead->state)" />
        </x-forms.field>
        <x-forms.field :label="__('Country')" name="country">
            <x-forms.input id="country" type="text" name="country" :value="old('country', $lead->country)" />
        </x-forms.field>
        <x-forms.field :label="__('Postal Code')" name="postal_code">
            <x-forms.input id="postal_code" type="text" name="postal_code" :value="old('postal_code', $lead->postal_code)" />
        </x-forms.field>
    </x-forms.section>

    <x-forms.section :title="__('Pipeline')" :subtitle="__('Status, source, and ownership')">
        <x-forms.field :label="__('Source')" name="source" required>
            <x-forms.select id="source" name="source" required>
                @foreach (config('leads.sources') as $value => $label)
                    <option value="{{ $value }}" @selected(old('source', $lead->source) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>

        <x-forms.field :label="__('Status')" name="status" required>
            <x-forms.select id="status" name="status" required>
                @foreach (config('leads.statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $lead->status) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>

        <x-forms.field :label="__('Priority')" name="priority" required>
            <x-forms.select id="priority" name="priority" required>
                @foreach (config('leads.priorities') as $value => $label)
                    <option value="{{ $value }}" @selected(old('priority', $lead->priority) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>

        <x-forms.field :label="__('Budget')" name="budget">
            <x-forms.input id="budget" type="number" name="budget" step="0.01" min="0" :value="old('budget', $lead->budget)" />
        </x-forms.field>

        <x-forms.field :label="__('Assigned To')" name="assigned_to">
            <x-forms.select id="assigned_to" name="assigned_to">
                <option value="">{{ __('Unassigned') }}</option>
                @foreach ($assignees as $member)
                    <option value="{{ $member->id }}" @selected(old('assigned_to', $lead->assigned_to) == $member->id)>{{ $member->name }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>

        <div class="sm:col-span-2">
            <x-forms.field :label="__('Tags')" name="tags" :hint="__('Comma-separated tags')">
                <x-forms.input id="tags" type="text" name="tags" :value="$tagsValue" placeholder="hot, enterprise, follow-up" />
            </x-forms.field>
        </div>
    </x-forms.section>

    <x-forms.section :title="__('Next Follow-up')" :subtitle="__('Schedule when to follow up — you will get an on-screen alert at this time.')">
        <x-forms.field :label="__('Follow-up Date & Time')" name="next_follow_up_at">
            @php
                $followUpService = app(\App\Services\LeadFollowUpService::class);
                $followUpInputValue = old('next_follow_up_at', $followUpService->formatForInput($lead->next_follow_up_at)
                    ?? $followUpService->formatForInput($followUpService->organizationNow()->copy()->addHour()));
            @endphp
            <x-follow-up-datetime-input
                id="next_follow_up_at"
                :value="$followUpInputValue"
                show-quick-pick
                class="mt-1"
            />
        </x-forms.field>

        <x-forms.field :label="__('Follow-up Notes')" name="next_follow_up_note">
            <x-forms.input id="next_follow_up_note" name="next_follow_up_note" type="text" :value="old('next_follow_up_note', $lead->next_follow_up_note)" placeholder="{{ __('Call to discuss pricing…') }}" />
        </x-forms.field>
    </x-forms.section>

    @include('metadata-fields._runtime_form', [
        'metadataFields' => $metadataFields ?? collect(),
        'metadataPresenter' => $metadataPresenter ?? app(\App\Services\MetadataFormValuePresenter::class),
        'record' => $lead,
    ])
</div>
