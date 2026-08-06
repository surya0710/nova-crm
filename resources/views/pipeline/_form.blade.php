<div class="space-y-8">
    <x-forms.section :title="__('Deal')" :subtitle="__('Title, customer, and description')">
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Deal Title')" name="title" required>
                <x-forms.input id="title" type="text" name="title" :value="old('title', $opportunity->title)" required placeholder="{{ __('Enterprise software license') }}" />
            </x-forms.field>
        </div>

        <x-forms.field :label="__('Customer')" name="customer_id">
            <x-forms.select id="customer_id" name="customer_id">
                <option value="">{{ __('Select customer…') }}</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('customer_id', $opportunity->customer_id) == $customer->id)>
                        {{ $customer->display_name }}
                    </option>
                @endforeach
            </x-forms.select>
        </x-forms.field>

        <x-forms.field :label="__('Related Lead')" name="lead_id">
            <x-forms.select id="lead_id" name="lead_id">
                <option value="">{{ __('None') }}</option>
                @foreach ($leads as $lead)
                    <option value="{{ $lead->id }}" @selected(old('lead_id', $opportunity->lead_id) == $lead->id)>
                        {{ $lead->name }}@if($lead->company) — {{ $lead->company }}@endif
                    </option>
                @endforeach
            </x-forms.select>
        </x-forms.field>

        <div class="sm:col-span-2">
            <x-forms.field :label="__('Description')" name="description">
                <x-forms.textarea id="description" name="description" rows="4">{{ old('description', $opportunity->description) }}</x-forms.textarea>
            </x-forms.field>
        </div>
    </x-forms.section>

    <x-forms.section :title="__('Pipeline')" :subtitle="__('Stage, ownership, and forecast')">
        <x-forms.field :label="__('Stage')" name="stage" required>
            @if ($opportunity->exists && $opportunity->isClosed())
                <p class="mt-1 text-sm text-ink-heading">{{ $opportunity->stage_label }}</p>
            @else
                <x-forms.select id="stage" name="stage" required>
                    @foreach (config('pipeline.open_stages') as $value)
                        <option value="{{ $value }}" @selected(old('stage', $opportunity->stage) === $value)>{{ config('pipeline.stages.'.$value) }}</option>
                    @endforeach
                </x-forms.select>
            @endif
        </x-forms.field>

        <x-forms.field :label="__('Assigned To')" name="assigned_to">
            <x-forms.select id="assigned_to" name="assigned_to">
                <option value="">{{ __('Unassigned') }}</option>
                @foreach ($assignees as $member)
                    <option value="{{ $member->id }}" @selected(old('assigned_to', $opportunity->assigned_to) == $member->id)>{{ $member->name }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>

        <x-forms.field :label="__('Deal Value')" name="amount">
            <x-forms.input id="amount" type="number" name="amount" step="0.01" min="0" :value="old('amount', $opportunity->amount)" />
        </x-forms.field>

        <x-forms.field :label="__('Currency')" name="currency" required>
            <x-forms.input id="currency" type="text" name="currency" maxlength="3" :value="old('currency', $opportunity->currency)" required />
        </x-forms.field>

        <x-forms.field :label="__('Probability (%)')" name="probability">
            <x-forms.input id="probability" type="number" name="probability" min="0" max="100" :value="old('probability', $opportunity->probability)" />
        </x-forms.field>

        <x-forms.field :label="__('Expected Close Date')" name="expected_close_date">
            <x-forms.input id="expected_close_date" type="date" name="expected_close_date" :value="old('expected_close_date', $opportunity->expected_close_date?->format('Y-m-d'))" />
        </x-forms.field>
    </x-forms.section>

    @include('metadata-fields._runtime_form', [
        'metadataFields' => $metadataFields ?? collect(),
        'metadataPresenter' => $metadataPresenter ?? app(\App\Services\MetadataFormValuePresenter::class),
        'record' => $opportunity,
    ])
</div>
