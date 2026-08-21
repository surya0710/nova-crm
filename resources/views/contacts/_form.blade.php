@php
    $contact = $contact ?? new App\Models\Contact(['status' => 'active']);
@endphp

<div class="space-y-8">
    <x-forms.section :title="__('Contact')">
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Name')" name="name" required>
                <x-forms.input id="name" type="text" name="name" :value="old('name', $contact->name)" required />
            </x-forms.field>
        </div>
        <x-forms.field :label="__('Title')" name="title">
            <x-forms.input id="title" type="text" name="title" :value="old('title', $contact->title)" />
        </x-forms.field>
        <x-forms.field :label="__('Department')" name="department">
            <x-forms.input id="department" type="text" name="department" :value="old('department', $contact->department)" />
        </x-forms.field>
        <x-forms.field :label="__('Email')" name="email">
            <x-forms.input id="email" type="email" name="email" :value="old('email', $contact->email)" />
        </x-forms.field>
        <x-forms.field :label="__('Phone')" name="phone">
            <x-forms.input id="phone" type="text" name="phone" :value="old('phone', $contact->phone)" />
        </x-forms.field>
        <x-forms.field :label="__('WhatsApp')" name="whatsapp">
            <x-forms.input id="whatsapp" type="text" name="whatsapp" :value="old('whatsapp', $contact->whatsapp)" />
        </x-forms.field>
        <x-forms.field :label="__('Status')" name="status" required>
            <x-forms.select id="status" name="status" required>
                @foreach (config('contacts.statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $contact->status) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <div class="sm:col-span-2 flex flex-col gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-ink">
                <input type="hidden" name="is_primary" value="0">
                <input type="checkbox" name="is_primary" value="1" class="rounded border-line text-primary-600 focus:ring-primary-500" @checked(old('is_primary', $contact->is_primary))>
                {{ __('Primary contact') }}
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-ink">
                <input type="hidden" name="is_decision_maker" value="0">
                <input type="checkbox" name="is_decision_maker" value="1" class="rounded border-line text-primary-600 focus:ring-primary-500" @checked(old('is_decision_maker', $contact->is_decision_maker))>
                {{ __('Decision maker') }}
            </label>
        </div>
    </x-forms.section>
</div>
