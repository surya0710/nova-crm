<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <x-input-label for="title" :value="__('Deal Title')" />
        <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title', $opportunity->title)" required placeholder="{{ __('Enterprise software license') }}" />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="customer_id" :value="__('Customer')" />
        <select id="customer_id" name="customer_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">{{ __('Select customer…') }}</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected(old('customer_id', $opportunity->customer_id) == $customer->id)>
                    {{ $customer->display_name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="lead_id" :value="__('Related Lead')" />
        <select id="lead_id" name="lead_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">{{ __('None') }}</option>
            @foreach ($leads as $lead)
                <option value="{{ $lead->id }}" @selected(old('lead_id', $opportunity->lead_id) == $lead->id)>
                    {{ $lead->name }}@if($lead->company) — {{ $lead->company }}@endif
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('lead_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="stage" :value="__('Stage')" />
        <select id="stage" name="stage" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
            @foreach (config('pipeline.stages') as $value => $label)
                <option value="{{ $value }}" @selected(old('stage', $opportunity->stage) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('stage')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="assigned_to" :value="__('Assigned To')" />
        <select id="assigned_to" name="assigned_to" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">{{ __('Unassigned') }}</option>
            @foreach ($assignees as $member)
                <option value="{{ $member->id }}" @selected(old('assigned_to', $opportunity->assigned_to) == $member->id)>{{ $member->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('assigned_to')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="amount" :value="__('Deal Value')" />
        <x-text-input id="amount" class="block mt-1 w-full" type="number" name="amount" step="0.01" min="0" :value="old('amount', $opportunity->amount)" />
        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="currency" :value="__('Currency')" />
        <x-text-input id="currency" class="block mt-1 w-full" type="text" name="currency" maxlength="3" :value="old('currency', $opportunity->currency)" required />
        <x-input-error :messages="$errors->get('currency')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="probability" :value="__('Probability (%)')" />
        <x-text-input id="probability" class="block mt-1 w-full" type="number" name="probability" min="0" max="100" :value="old('probability', $opportunity->probability)" />
        <x-input-error :messages="$errors->get('probability')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="expected_close_date" :value="__('Expected Close Date')" />
        <x-text-input id="expected_close_date" class="block mt-1 w-full" type="date" name="expected_close_date" :value="old('expected_close_date', $opportunity->expected_close_date?->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('expected_close_date')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" :value="__('Description')" />
        <textarea id="description" name="description" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $opportunity->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>
</div>
