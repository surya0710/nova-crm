@props(['opportunity'])

{{-- Mark Won modal --}}
<x-modal name="opportunity-mark-won" :show="$errors->has('won_at') && old('stage') === 'closed_won'" focusable>
    <form method="POST" action="{{ route('pipeline.stage.update', $opportunity) }}" class="p-6">
        @csrf
        @method('PATCH')
        <input type="hidden" name="stage" value="closed_won">

        <h2 class="text-lg font-medium text-gray-900">{{ __('Mark Deal as Won') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Record when this deal was won.') }}</p>

        <div class="mt-6">
            <x-input-label for="won_at" :value="__('Won Date')" />
            <x-text-input
                id="won_at"
                name="won_at"
                type="date"
                class="block mt-1 w-full"
                :value="old('won_at', now()->format('Y-m-d'))"
                required
            />
            <x-input-error :messages="$errors->get('won_at')" class="mt-2" />
            <x-input-error :messages="$errors->get('stage')" class="mt-2" />
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'opportunity-mark-won')">
                {{ __('Cancel') }}
            </x-secondary-button>
            <x-primary-button>{{ __('Mark as Won') }}</x-primary-button>
        </div>
    </form>
</x-modal>

{{-- Mark Lost modal --}}
<x-modal name="opportunity-mark-lost" :show="$errors->has('lost_reason') && old('stage') === 'closed_lost'" focusable>
    <form method="POST" action="{{ route('pipeline.stage.update', $opportunity) }}" class="p-6">
        @csrf
        @method('PATCH')
        <input type="hidden" name="stage" value="closed_lost">

        <h2 class="text-lg font-medium text-gray-900">{{ __('Mark Deal as Lost') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Provide a reason this deal was lost.') }}</p>

        <div class="mt-6">
            <x-input-label for="lost_reason" :value="__('Lost Reason')" />
            <textarea
                id="lost_reason"
                name="lost_reason"
                rows="3"
                list="lost-reason-suggestions"
                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                placeholder="{{ __('Why was this deal lost?') }}"
                required
            >{{ old('lost_reason') }}</textarea>
            <datalist id="lost-reason-suggestions">
                @foreach (config('pipeline.lost_reasons', []) as $reason)
                    <option value="{{ $reason }}"></option>
                @endforeach
            </datalist>
            <x-input-error :messages="$errors->get('lost_reason')" class="mt-2" />
            <x-input-error :messages="$errors->get('stage')" class="mt-2" />
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <x-secondary-button type="button" x-on:click="$dispatch('close-modal', 'opportunity-mark-lost')">
                {{ __('Cancel') }}
            </x-secondary-button>
            <x-primary-button class="!bg-slate-700 hover:!bg-slate-800">{{ __('Mark as Lost') }}</x-primary-button>
        </div>
    </form>
</x-modal>
