@props([
    'action',
    'email' => '',
    'submitLabel' => null,
    'title' => __('Send by Email'),
    'description' => null,
    'showSubject' => false,
    'subject' => '',
    'organization' => null,
    'missingEmailHint' => false,
])

@php
    $submitLabel = $submitLabel ?? __('Send Email');
    $maxFiles = (int) config('crm.email_attachments.max_files', 5);
    $maxMb = round((int) config('crm.email_attachments.max_size_kb', 10240) / 1024, 1);
    $mailConfigured = $organization
        ? app(\App\Services\OrganizationMailConfig::class)->for($organization)->isConfigured()
        : false;
    $mailFrom = $organization
        ? app(\App\Services\OrganizationMailConfig::class)->for($organization)->displayFrom()
        : null;
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden']) }}>
    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
        <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
        @if ($description)
            <p class="text-sm text-slate-500 mt-0.5">{{ $description }}</p>
        @endif
    </div>
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="p-6 space-y-4">
        @csrf

        @unless ($mailConfigured)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __('Organization email is not configured.') }}
                @if (Auth::user()->hasPermission('settings.manage', $organization))
                    <a href="{{ route('organization.edit') }}" class="font-medium underline hover:text-amber-950">{{ __('Set up SMTP in Settings → Email') }}</a>
                @else
                    {{ __('Ask your organization owner to configure SMTP under Settings → Email.') }}
                @endif
            </div>
        @endunless

        @if ($showSubject)
            <div>
                <x-input-label for="subject" :value="__('Subject')" />
                <x-text-input id="subject" class="block mt-1 w-full" type="text" name="subject" :value="old('subject', $subject)" required />
                <x-input-error :messages="$errors->get('subject')" class="mt-2" />
            </div>
        @endif

        <div>
            <x-input-label for="client-email" :value="__('Recipient email')" />
            <x-text-input
                id="client-email"
                class="block mt-1 w-full"
                type="email"
                name="email"
                :value="old('email', $email)"
                placeholder="customer@example.com"
                required
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            @if ($missingEmailHint)
                <p class="mt-1 text-xs text-amber-600">{{ __('This customer has no email on file — enter one above.') }}</p>
            @endif
        </div>

        <div>
            <x-input-label for="client-message" :value="__('Message')" />
            <textarea
                id="client-message"
                name="message"
                rows="3"
                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                placeholder="{{ __('Write your message to the client…') }}"
            >{{ old('message') }}</textarea>
            <x-input-error :messages="$errors->get('message')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="client-attachments" :value="__('Attachments (optional)')" />
            <input
                id="client-attachments"
                type="file"
                name="attachments[]"
                multiple
                class="block mt-1 w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
            />
            <p class="mt-1 text-xs text-slate-500">
                {{ __('Up to :count files, :size MB each. PDF, images, Office docs, CSV, TXT, ZIP.', ['count' => $maxFiles, 'size' => $maxMb]) }}
            </p>
            <x-input-error :messages="$errors->get('attachments')" class="mt-2" />
            <x-input-error :messages="$errors->get('attachments.*')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-3 flex-wrap">
            <p class="text-xs text-slate-500">
                @if ($mailConfigured && $mailFrom)
                    {{ __('Sent from :address', ['address' => $mailFrom]) }}
                @else
                    {{ __('Configure organization SMTP to send client emails.') }}
                @endif
            </p>
            <x-primary-button type="submit" :disabled="! $mailConfigured">{{ $submitLabel }}</x-primary-button>
        </div>
    </form>
</div>
