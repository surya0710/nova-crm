<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$contact->name"
        :subtitle="$contact->customer?->display_name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Contacts'), 'href' => route('contacts.index')],
                ['label' => $contact->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('update', $contact)
                <x-ui.button :href="route('contacts.show', $contact).'#email-composer'" variant="secondary" size="sm">{{ __('Email') }}</x-ui.button>
                <x-ui.button :href="route('contacts.edit', $contact)" variant="secondary" size="sm">{{ __('Edit') }}</x-ui.button>
            @endcan
            @can('delete', $contact)
                <form method="POST" action="{{ route('contacts.destroy', $contact) }}" onsubmit="return confirm('{{ __('Delete this contact?') }}')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-ui.button>
                </form>
            @endcan
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge :variant="$contact->status === 'active' ? 'success' : 'neutral'">{{ $contact->status_label }}</x-ui.badge>
            @if ($contact->is_primary)
                <x-ui.badge variant="primary">{{ __('Primary') }}</x-ui.badge>
            @endif
            @if ($contact->is_decision_maker)
                <x-ui.badge variant="info">{{ __('Decision maker') }}</x-ui.badge>
            @endif
        </x-slot:tabs>

        <x-entity.section :title="__('Details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Company')">
                    @if ($contact->customer)
                        <a href="{{ route('customers.show', $contact->customer) }}" class="text-primary-600 hover:text-primary-700">{{ $contact->customer->display_name }}</a>
                    @else
                        —
                    @endif
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Title')">{{ $contact->title ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Department')">{{ $contact->department ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Email')">{{ $contact->email ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Phone')">{{ $contact->phone ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('WhatsApp')">{{ $contact->whatsapp ?? '—' }}</x-entity.definition-item>
            </x-entity.definition-list>
        </x-entity.section>

        <x-entity.section :title="__('Timeline')">
            <x-activity.timeline
                :empty="($timelineItems ?? collect())->isEmpty()"
                :empty-title="__('No activity yet')"
                :empty-description="__('Log a note, call, meeting, or follow-up after each conversation.')"
            >
                @can('update', $contact)
                    <x-slot:composer>
                        <form method="POST" action="{{ route('contacts.notes.store', $contact) }}" class="space-y-3">
                            @csrf
                            <x-forms.field :label="__('Add a note')" name="body" required>
                                <x-forms.textarea id="body" name="body" rows="3" required>{{ old('body') }}</x-forms.textarea>
                            </x-forms.field>
                            <div class="flex justify-end gap-2">
                                <x-ui.button :href="'#email-composer'" variant="secondary" size="sm">{{ __('Email') }}</x-ui.button>
                                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add note') }}</x-ui.button>
                            </div>
                        </form>
                        <details class="mt-4 rounded-lg border border-line p-3">
                            <summary class="cursor-pointer text-sm font-semibold text-ink-heading">{{ __('Log activity') }}</summary>
                            <form method="POST" action="{{ route('contacts.activities.store', $contact) }}" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @csrf
                                <x-forms.select name="type" aria-label="{{ __('Activity type') }}" required>
                                    @foreach (config('crm_activities.types') ?? [] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type', 'call') === $value)>{{ $label }}</option>
                                    @endforeach
                                </x-forms.select>
                                <x-forms.input name="subject" :value="old('subject')" placeholder="{{ __('Subject') }}" required />
                                <x-forms.input type="datetime-local" name="occurred_at" :value="old('occurred_at')" aria-label="{{ __('Occurred at') }}" />
                                <x-forms.input type="datetime-local" name="due_at" :value="old('due_at')" aria-label="{{ __('Follow-up due') }}" />
                                <x-forms.select name="direction" aria-label="{{ __('Direction') }}">
                                    <option value="">{{ __('Direction') }}</option>
                                    @foreach (config('crm_activities.directions') ?? [] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('direction') === $value)>{{ $label }}</option>
                                    @endforeach
                                </x-forms.select>
                                <x-forms.select name="outcome" aria-label="{{ __('Outcome') }}">
                                    <option value="">{{ __('Outcome') }}</option>
                                    @foreach (config('crm_activities.outcomes') ?? [] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('outcome') === $value)>{{ $label }}</option>
                                    @endforeach
                                </x-forms.select>
                                <x-forms.input type="number" name="duration_minutes" :value="old('duration_minutes')" min="1" max="1440" placeholder="{{ __('Duration (minutes)') }}" />
                                <div class="sm:col-span-2">
                                    <x-forms.textarea name="body" rows="3" placeholder="{{ __('Details') }}">{{ old('activity_body', old('body')) }}</x-forms.textarea>
                                </div>
                                <div class="sm:col-span-2 flex justify-end">
                                    <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Log activity') }}</x-ui.button>
                                </div>
                            </form>
                        </details>
                    </x-slot:composer>
                @endcan
                @foreach ($timelineItems ?? [] as $item)
                    <x-activity.timeline-item :actor="$item['actor'] ?? null" :timestamp="$item['timestamp'] ?? null" :type="$item['type'] ?? 'note'">
                        <x-slot:label>{{ $item['label'] }}</x-slot:label>
                        @if (! empty($item['href']))
                            <a href="{{ $item['href'] }}" class="text-primary-600 hover:text-primary-700">{{ $item['body'] }}</a>
                        @else
                            {{ $item['body'] }}
                        @endif
                    </x-activity.timeline-item>
                @endforeach
            </x-activity.timeline>
        </x-entity.section>

        <x-crm-email-conversations :related="$contact" />

        <x-tasks-panel
            taskable-type="contact"
            :taskable-id="$contact->id"
            :tasks="$contact->tasks"
            :can-create="auth()->user()->can('tasks.create')"
        />

        @can('update', $contact)
            <x-client-email-form
                :action="route('contacts.send', $contact)"
                :email="old('email', $contact->email ?? $contact->customer?->email)"
                :submit-label="__('Send Email')"
                :title="__('Email Contact')"
                :description="__('Send a message to this person and log it on the customer timeline.')"
                :organization="$organization ?? $contact->organization"
                :show-subject="true"
                :subject="old('subject', __('Message from :name', ['name' => ($organization ?? $contact->organization)?->name ?? config('app.name')]))"
                :missing-email-hint="! $contact->email"
                :show-bcc="true"
                :module="'contacts'"
                :related="$contact"
                :suggested-recipients="collect([$contact->email, $contact->customer?->email])->filter()->unique()->values()"
            />
        @endcan
    </x-layouts.entity-detail>
</x-app-layout>
