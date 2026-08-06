<form id="onboarding-step-form" method="post" action="{{ route('platform.onboarding.steps', $onboarding) }}" class="mt-6 space-y-6">
    @csrf
    <input type="hidden" name="step" value="structure">

    <x-ui.alert variant="info">
        {{ __('Create a starter structure now, skip for later, or import masters via Import Center in the next steps.') }}
    </x-ui.alert>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-forms.field :label="__('Branch name')" name="branch[name]">
            <x-forms.input name="branch[name]" value="{{ old('branch.name', $stepData['branch']['name'] ?? 'Head Office') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Branch code')" name="branch[code]">
            <x-forms.input name="branch[code]" value="{{ old('branch.code', $stepData['branch']['code'] ?? 'HO') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Department name')" name="department[name]">
            <x-forms.input name="department[name]" value="{{ old('department.name', $stepData['department']['name'] ?? 'General') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Designation name')" name="designation[name]">
            <x-forms.input name="designation[name]" value="{{ old('designation.name', $stepData['designation']['name'] ?? 'Employee') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Shift name')" name="shift[name]">
            <x-forms.input name="shift[name]" value="{{ old('shift.name', $stepData['shift']['name'] ?? 'General Shift') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Shift hours')" name="shift_hours">
            <div class="flex gap-2">
                <x-forms.input name="shift[start_time]" value="{{ old('shift.start_time', $stepData['shift']['start_time'] ?? '09:00') }}" placeholder="09:00" />
                <x-forms.input name="shift[end_time]" value="{{ old('shift.end_time', $stepData['shift']['end_time'] ?? '18:00') }}" placeholder="18:00" />
            </div>
        </x-forms.field>
    </div>

    @include('platform.onboarding.partials.actions')
</form>
