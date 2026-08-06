<x-app-layout>
    <x-ui.page-header
        :title="__('API Tokens')"
        :subtitle="__('Manage personal access tokens for the REST API')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => \Illuminate\Support\Facades\Route::has('administration.home') ? route('administration.home') : null],
                ['label' => __('API Tokens'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>
    </x-ui.page-header>

    <x-flash-messages />

    @if ($plainTextToken)
        <div
            x-data="{
                token: @js($plainTextToken),
                copied: false,
                async copyToken() {
                    try {
                        await navigator.clipboard.writeText(this.token);
                    } catch (e) {
                        const input = document.createElement('textarea');
                        input.value = this.token;
                        input.setAttribute('readonly', '');
                        input.style.position = 'absolute';
                        input.style.left = '-9999px';
                        document.body.appendChild(input);
                        input.select();
                        document.execCommand('copy');
                        document.body.removeChild(input);
                    }
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                }
            }"
            x-init="copyToken()"
            class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ __('Copy your new API token now') }}</p>
                    <p class="mt-1 break-all font-mono text-xs" x-text="token">{{ $plainTextToken }}</p>
                    <p class="mt-2 text-xs">{{ __('This token will not be shown again.') }}</p>
                    <p class="mt-1 text-xs text-amber-700" x-show="copied" x-cloak>{{ __('Token copied to clipboard.') }}</p>
                </div>
                <button
                    type="button"
                    @click="copyToken()"
                    class="inline-flex shrink-0 items-center rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-amber-900 shadow-sm hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                >
                    <span x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy') }}'">{{ __('Copy') }}</span>
                </button>
            </div>
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
