@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <x-ui.page-header :title="__('Create Attendance Period')" />

        <x-ui.card>
            <form method="POST" action="{{ route('hrms.attendance.periods.store') }}" class="space-y-4">
                @csrf
                <x-forms.field :label="__('Name')" name="name" required>
                    <x-forms.input name="name" :value="old('name')" required />
                </x-forms.field>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-forms.field :label="__('Start date')" name="start_date" required>
                        <x-forms.input type="date" name="start_date" :value="old('start_date')" required />
                    </x-forms.field>
                    <x-forms.field :label="__('End date')" name="end_date" required>
                        <x-forms.input type="date" name="end_date" :value="old('end_date')" required />
                    </x-forms.field>
                </div>
                <x-forms.field :label="__('Payroll period')" name="payroll_period_id">
                    <x-forms.select name="payroll_period_id">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($payrollPeriods as $payrollPeriod)
                            <option value="{{ $payrollPeriod->id }}" @selected((string) old('payroll_period_id') === (string) $payrollPeriod->id)>
                                {{ $payrollPeriod->name }} ({{ $payrollPeriod->start_date->toDateString() }} → {{ $payrollPeriod->end_date->toDateString() }})
                            </option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="flex gap-2">
                    <x-ui.button type="submit" size="sm">{{ __('Create') }}</x-ui.button>
                    <x-ui.button :href="route('hrms.attendance.periods.index')" variant="secondary" size="sm">{{ __('Cancel') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
