<x-forms.section>
<x-forms.field :label="__('Name')" name="name" required>
    <x-forms.input name="name" value="{{ old('name', $user->name ?? '') }}" required />
</x-forms.field>

<x-forms.field :label="__('Email')" name="email" required>
    <x-forms.input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required />
</x-forms.field>

<x-forms.field :label="__('Password')" name="password" :hint="isset($user) ? __('Leave blank to keep the current password') : null" :required="! isset($user)">
    <x-forms.input type="password" name="password" autocomplete="new-password" @required(! isset($user)) />
</x-forms.field>

<x-forms.field :label="__('Role')" name="role" required>
    <x-forms.select name="role" required>
        @foreach ($roles as $slug => $role)
            <option value="{{ $slug }}" @selected(old('role', $user->role ?? '') === $slug)>{{ $role['name'] }}</option>
        @endforeach
    </x-forms.select>
</x-forms.field>

<x-forms.field :label="__('Status')" name="status" required>
    <x-forms.select name="status" required>
        <option value="active" @selected(old('status', $user->status ?? 'active') === 'active')>{{ __('Active') }}</option>
        <option value="inactive" @selected(old('status', $user->status ?? '') === 'inactive')>{{ __('Inactive') }}</option>
    </x-forms.select>
</x-forms.field>
</x-forms.section>
