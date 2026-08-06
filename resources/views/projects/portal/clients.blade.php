<x-app-layout>
    <x-flash-messages />
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold">{{ __('Client Portal Access') }}</h1>
                    <p class="text-sm text-slate-500">{{ $project->name }}</p>
                </div>
                <a href="{{ route('projects.show', $project) }}" class="text-sm underline">{{ __('Back to project') }}</a>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="font-medium">{{ __('Invite client') }}</h2>
                <form method="POST" action="{{ route('projects.portal.clients.invite', $project) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label class="text-sm">{{ __('Customer') }}</label>
                        <select name="customer_id" class="mt-1 w-full rounded-lg border-slate-300" required>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id', $project->client_id) == $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm">{{ __('Name') }}</label>
                        <input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-lg border-slate-300" required>
                    </div>
                    <div>
                        <label class="text-sm">{{ __('Email') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border-slate-300" required>
                    </div>
                    <div>
                        <label class="text-sm">{{ __('Temporary password') }}</label>
                        <input type="text" name="password" class="mt-1 w-full rounded-lg border-slate-300" placeholder="{{ __('Optional') }}">
                    </div>
                    <div class="sm:col-span-2">
                        <div class="text-sm mb-1">{{ __('Scopes') }}</div>
                        <div class="flex flex-wrap gap-3 text-sm">
                            @foreach (config('portal.share_scopes') as $scope => $label)
                                <label class="inline-flex items-center gap-1">
                                    <input type="checkbox" name="scopes[]" value="{{ $scope }}" @checked(in_array($scope, config('portal.default_share_scopes'), true))>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Invite & grant access') }}</button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 font-medium">{{ __('Clients') }}</div>
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-2">{{ __('Name') }}</th>
                            <th class="px-4 py-2">{{ __('Email') }}</th>
                            <th class="px-4 py-2">{{ __('Access') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                            @php $access = $client->projectAccess->first(); @endphp
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-2">{{ $client->name }}</td>
                                <td class="px-4 py-2">{{ $client->email }}</td>
                                <td class="px-4 py-2">{{ $access ? __('Granted') : __('None') }}</td>
                                <td class="px-4 py-2 text-right space-x-2">
                                    @if (!$access)
                                        <form method="POST" action="{{ route('projects.portal.clients.grant', [$project, $client]) }}" class="inline">@csrf<button class="underline">{{ __('Grant') }}</button></form>
                                    @else
                                        <form method="POST" action="{{ route('projects.portal.clients.revoke', [$project, $client]) }}" class="inline">@csrf @method('DELETE')<button class="underline text-rose-600">{{ __('Revoke') }}</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('No client users for this customer yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-slate-500">
                {{ __('Portal login URL:') }}
                <code>{{ url($project->organization->slug.'/portal/login') }}</code>
            </p>
        </div>
    </div>
</x-app-layout>
