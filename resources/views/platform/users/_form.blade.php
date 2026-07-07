<div>
    <label class="block text-sm text-slate-400 mb-1">{{ __('Name') }}</label>
    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required class="w-full rounded-lg bg-slate-800 border-slate-700 text-white text-sm" />
</div>
<div>
    <label class="block text-sm text-slate-400 mb-1">{{ __('Email') }}</label>
    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required class="w-full rounded-lg bg-slate-800 border-slate-700 text-white text-sm" />
</div>
<div>
    <label class="block text-sm text-slate-400 mb-1">{{ __('Password') }} @isset($user)<span class="text-slate-500">({{ __('leave blank to keep') }})</span>@endisset</label>
    <input type="password" name="password" @empty($user) required @endempty class="w-full rounded-lg bg-slate-800 border-slate-700 text-white text-sm" />
</div>
<div>
    <label class="block text-sm text-slate-400 mb-1">{{ __('Role') }}</label>
    <select name="role" required class="w-full rounded-lg bg-slate-800 border-slate-700 text-white text-sm">
        @foreach ($roles as $slug => $role)
            <option value="{{ $slug }}" @selected(old('role', $user->role ?? '') === $slug)>{{ $role['name'] }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="block text-sm text-slate-400 mb-1">{{ __('Status') }}</label>
    <select name="status" required class="w-full rounded-lg bg-slate-800 border-slate-700 text-white text-sm">
        <option value="active" @selected(old('status', $user->status ?? 'active') === 'active')>{{ __('Active') }}</option>
        <option value="inactive" @selected(old('status', $user->status ?? '') === 'inactive')>{{ __('Inactive') }}</option>
    </select>
</div>
