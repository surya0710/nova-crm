@php
    $statusColors = [
        'new' => 'bg-blue-100 text-blue-800',
        'contacted' => 'bg-cyan-100 text-cyan-800',
        'qualified' => 'bg-indigo-100 text-indigo-800',
        'proposal_sent' => 'bg-violet-100 text-violet-800',
        'negotiation' => 'bg-amber-100 text-amber-800',
        'won' => 'bg-emerald-100 text-emerald-800',
        'lost' => 'bg-slate-100 text-slate-600',
    ];
    $priorityColors = [
        'low' => 'bg-slate-100 text-slate-600',
        'medium' => 'bg-amber-100 text-amber-800',
        'high' => 'bg-red-100 text-red-800',
    ];
    $tagsValue = old('tags', isset($lead->tags) ? implode(', ', $lead->tags ?? []) : '');
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <x-input-label for="name" :value="__('Contact Name')" />
        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $lead->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="company" :value="__('Company')" />
        <x-text-input id="company" class="block mt-1 w-full" type="text" name="company" :value="old('company', $lead->company)" />
        <x-input-error :messages="$errors->get('company')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="industry" :value="__('Industry')" />
        <x-text-input id="industry" class="block mt-1 w-full" type="text" name="industry" :value="old('industry', $lead->industry)" />
        <x-input-error :messages="$errors->get('industry')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" :value="__('Email')" />
        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $lead->email)" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="phone" :value="__('Phone')" />
        <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone', $lead->phone)" />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="source" :value="__('Source')" />
        <select id="source" name="source" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
            @foreach (config('leads.sources') as $value => $label)
                <option value="{{ $value }}" @selected(old('source', $lead->source) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('source')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" :value="__('Status')" />
        <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
            @foreach (config('leads.statuses') as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $lead->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="priority" :value="__('Priority')" />
        <select id="priority" name="priority" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
            @foreach (config('leads.priorities') as $value => $label)
                <option value="{{ $value }}" @selected(old('priority', $lead->priority) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('priority')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="budget" :value="__('Budget')" />
        <x-text-input id="budget" class="block mt-1 w-full" type="number" name="budget" step="0.01" min="0" :value="old('budget', $lead->budget)" />
        <x-input-error :messages="$errors->get('budget')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="assigned_to" :value="__('Assigned To')" />
        <select id="assigned_to" name="assigned_to" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">{{ __('Unassigned') }}</option>
            @foreach ($assignees as $member)
                <option value="{{ $member->id }}" @selected(old('assigned_to', $lead->assigned_to) == $member->id)>{{ $member->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('assigned_to')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="tags" :value="__('Tags')" />
        <x-text-input id="tags" class="block mt-1 w-full" type="text" name="tags" :value="$tagsValue" placeholder="hot, enterprise, follow-up" />
        <p class="mt-1 text-xs text-slate-500">{{ __('Comma-separated tags') }}</p>
        <x-input-error :messages="$errors->get('tags')" class="mt-2" />
    </div>

    <div class="sm:col-span-2 pt-2 border-t border-slate-100">
        <h4 class="text-sm font-semibold text-slate-900">{{ __('Next Follow-up') }}</h4>
        <p class="text-xs text-slate-500 mt-1">{{ __('Schedule when to follow up — you will get an on-screen alert at this time.') }}</p>
    </div>

    <div>
        <x-input-label for="next_follow_up_at" :value="__('Follow-up Date & Time')" />
        <x-text-input id="next_follow_up_at" name="next_follow_up_at" class="block mt-1 w-full" type="datetime-local" :value="old('next_follow_up_at', $lead->next_follow_up_at?->format('Y-m-d\TH:i'))" />
        <x-input-error :messages="$errors->get('next_follow_up_at')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="next_follow_up_note" :value="__('Follow-up Notes')" />
        <x-text-input id="next_follow_up_note" name="next_follow_up_note" class="block mt-1 w-full" type="text" :value="old('next_follow_up_note', $lead->next_follow_up_note)" placeholder="{{ __('Call to discuss pricing…') }}" />
        <x-input-error :messages="$errors->get('next_follow_up_note')" class="mt-2" />
    </div>
</div>
