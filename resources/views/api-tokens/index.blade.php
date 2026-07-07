<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('API Tokens') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage personal access tokens for the REST API') }}</p>
        </div>
    </x-slot>

    <x-flash-messages />

    @if ($plainTextToken)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">{{ __('Copy your new API token now') }}</p>
            <p class="mt-1 break-all font-mono text-xs">{{ $plainTextToken }}</p>
            <p class="mt-2 text-xs">{{ __('This token will not be shown again.') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b bg-slate-50/50"><h3 class="font-semibold text-slate-900">{{ __('Create token') }}</h3></div>
            <form method="POST" action="{{ route('api-tokens.store') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <x-input-label for="name" :value="__('Token name')" />
                    <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name')" placeholder="Mobile app, Zapier, etc." required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <x-primary-button type="submit">{{ __('Create Token') }}</x-primary-button>
            </form>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b bg-slate-50/50"><h3 class="font-semibold text-slate-900">{{ __('Active tokens') }}</h3></div>
            <div class="p-6">
                @if ($tokens->isEmpty())
                    <p class="text-sm text-slate-500">{{ __('No API tokens yet.') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach ($tokens as $token)
                            <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ $token->name }}</p>
                                    <p class="text-xs text-slate-500">{{ __('Created') }} {{ $token->created_at->diffForHumans() }}</p>
                                </div>
                                <form method="POST" action="{{ route('api-tokens.destroy', $token->id) }}" onsubmit="return confirm('{{ __('Revoke this token?') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800">{{ __('Revoke') }}</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-xl bg-white border border-slate-200 shadow-sm p-6 text-sm text-slate-600">
        <p class="font-semibold text-slate-900 mb-2">{{ __('API base URL') }}</p>
        <code class="text-xs bg-slate-100 px-2 py-1 rounded">{{ url('/api/v1') }}</code>
        <p class="mt-4">{{ __('Send the token as a Bearer token in the Authorization header. Organization context uses your current workspace from the web session when creating tokens, or pass organization via future API headers.') }}</p>
    </div>
</x-app-layout>
