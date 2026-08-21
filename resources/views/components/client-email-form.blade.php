@props([
    'action',
    'email' => '',
    'submitLabel' => null,
    'title' => __('Send by Email'),
    'description' => null,
    'showSubject' => false,
    'subject' => '',
    'body' => '',
    'organization' => null,
    'missingEmailHint' => false,
    'showCc' => true,
    'showBcc' => true,
    'ccSender' => false,
    'module' => null,
    'related' => null,
    'suggestedRecipients' => [],
    'defaultCc' => '',
    'inReplyTo' => '',
    'threadId' => '',
    'references' => '',
])

@php
    $submitLabel = $submitLabel ?? __('Send Email');
    $maxFiles = (int) config('crm.email_attachments.max_files', 5);
    $maxMb = round((int) config('crm.email_attachments.max_size_kb', 10240) / 1024, 1);
    $mailConfig = $organization
        ? app(\App\Services\OrganizationMailConfig::class)->for($organization)
        : null;
    $mailConfigured = $mailConfig?->isConfigured() ?? false;
    $mailFrom = $mailConfig?->displayFrom();
    $signature = $mailConfig?->signature();
    $templates = $organization
        ? app(\App\Services\CrmEmailTemplateService::class)->forComposer($organization, $module, $related)
        : collect();
    $suggestedRecipients = collect($suggestedRecipients)
        ->filter()
        ->unique()
        ->values();
    $defaultCc = $defaultCc !== '' ? $defaultCc : old('cc');
@endphp

<div
    id="email-composer"
    {{ $attributes->merge(['class' => 'scroll-mt-24 rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden']) }}
    x-data="{
        templates: {{ \Illuminate\Support\Js::from($templates) }},
        subject: {{ \Illuminate\Support\Js::from(old('subject', $subject)) }},
        body: {{ \Illuminate\Support\Js::from(old('message', $body)) }},
        to: {{ \Illuminate\Support\Js::from(old('email', $email)) }},
        templateId: {{ \Illuminate\Support\Js::from(old('template_id')) }},
        includeSignature: true,
        applyTemplate() {
            const selected = this.templates.find(t => String(t.id) === String(this.templateId));
            if (! selected) return;
            this.subject = selected.subject;
            this.body = selected.body;
        },
        addRecipient(address) {
            const current = (this.to || '').split(/[,;]+/).map(v => v.trim()).filter(Boolean);
            if (! current.map(v => v.toLowerCase()).includes(address.toLowerCase())) {
                current.push(address);
            }
            this.to = current.join(', ');
        }
    }"
>
    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
        <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
        @if ($description)
            <p class="text-sm text-slate-500 mt-0.5">{{ $description }}</p>
        @endif
    </div>
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="p-6 space-y-4">
        @csrf
        <input type="hidden" name="in_reply_to" value="{{ old('in_reply_to', $inReplyTo ?? '') }}">
        <input type="hidden" name="thread_id" value="{{ old('thread_id', $threadId ?? '') }}">
        <input type="hidden" name="references" value="{{ old('references', $references ?? '') }}">

        @unless ($mailConfigured)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __('Organization email is not configured.') }}
                @if ($organization && Auth::user()->hasPermission('settings.manage', $organization))
                    <a href="{{ route('organization.edit', ['tab' => 'email']) }}" class="font-medium underline hover:text-amber-950">{{ __('Set up SMTP in Settings → Email') }}</a>
                @else
                    {{ __('Ask your organization owner to configure SMTP under Settings → Email.') }}
                @endif
            </div>
        @endunless

        @if ($templates->isNotEmpty())
            <div>
                <x-input-label for="crm-email-template" :value="__('Email template')" />
                <select
                    id="crm-email-template"
                    name="template_id"
                    x-model="templateId"
                    x-on:change="applyTemplate()"
                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                    <option value="">{{ __('No template') }}</option>
                    @foreach ($templates as $template)
                        <option value="{{ $template['id'] }}">{{ $template['name'] }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('template_id')" class="mt-2" />
            </div>
        @endif

        @if ($showSubject)
            <div>
                <x-input-label for="subject" :value="__('Subject')" />
                <x-text-input id="subject" class="block mt-1 w-full" type="text" name="subject" x-model="subject" required />
                <x-input-error :messages="$errors->get('subject')" class="mt-2" />
            </div>
        @endif

        <div>
            <x-input-label for="client-email" :value="__('To')" />
            <x-text-input
                id="client-email"
                class="block mt-1 w-full"
                type="text"
                name="email"
                x-model="to"
                placeholder="customer@example.com, ap@example.com"
                required
            />
            <p class="mt-1 text-xs text-slate-500">{{ __('Separate multiple recipients with commas.') }}</p>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            @if ($missingEmailHint)
                <p class="mt-1 text-xs text-amber-600">{{ __('No email is on file — enter one or more recipients above.') }}</p>
            @endif
            @if ($suggestedRecipients->isNotEmpty())
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($suggestedRecipients as $address)
                        <button
                            type="button"
                            class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs text-slate-700 hover:border-indigo-300 hover:text-indigo-700"
                            x-on:click="addRecipient({{ \Illuminate\Support\Js::from($address) }})"
                        >{{ $address }}</button>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($showCc || $ccSender)
        <div>
            <x-input-label for="client-cc" :value="__('CC (optional)')" />
            <x-text-input
                id="client-cc"
                class="block mt-1 w-full"
                type="text"
                name="cc"
                :value="$defaultCc"
                placeholder="accounts@example.com, finance@example.com"
            />
            @if ($ccSender)
                <p class="mt-1 text-xs text-slate-500">{{ __('Your address is added automatically. Separate additional recipients with commas.') }}</p>
            @endif
            <x-input-error :messages="$errors->get('cc')" class="mt-2" />
        </div>
        @endif

        @if ($showBcc)
        <div>
            <x-input-label for="client-bcc" :value="__('BCC (optional)')" />
            <x-text-input
                id="client-bcc"
                class="block mt-1 w-full"
                type="text"
                name="bcc"
                :value="old('bcc')"
                placeholder="archive@example.com"
            />
            <x-input-error :messages="$errors->get('bcc')" class="mt-2" />
        </div>
        @endif

        <div>
            <x-input-label for="client-message" :value="__('Message')" />
            <textarea
                id="client-message"
                name="message"
                rows="6"
                x-model="body"
                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                placeholder="{{ __('Write your message to the client…') }}"
            ></textarea>
            <x-input-error :messages="$errors->get('message')" class="mt-2" />
        </div>

        @if ($signature)
            <label class="inline-flex items-start gap-2 text-sm text-slate-700">
                <input type="hidden" name="include_signature" value="0">
                <input type="checkbox" name="include_signature" value="1" x-model="includeSignature" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked>
                <span>
                    <span class="font-medium">{{ __('Include organization signature') }}</span>
                    <span class="mt-1 block whitespace-pre-line text-xs text-slate-500">{{ $signature }}</span>
                </span>
            </label>
        @endif

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
