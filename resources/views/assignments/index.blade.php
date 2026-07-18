<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Assignment Settings') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Pools, rules, and strategies for :org', ['org' => $organization->name]) }}</p>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="max-w-5xl space-y-10">
        {{-- Assignment Pools --}}
        <section class="space-y-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">{{ __('Assignment Pools') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Define who can receive assignments and which strategy to use.') }}</p>
            </div>

            <div class="space-y-4">
                @forelse ($pools as $pool)
                    <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <form method="POST" action="{{ route('assignments.pools.update', $pool) }}" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Name') }}</label>
                                    <input type="text" name="name" value="{{ old('name', $pool->name) }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Strategy') }}</label>
                                    <select name="strategy" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                                        @foreach ($strategies as $key => $label)
                                            <option value="{{ $key }}" @selected(old('strategy', $pool->strategy) === $key)>{{ __($label) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">{{ __('Description') }}</label>
                                <textarea name="description" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm">{{ old('description', $pool->description) }}</textarea>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" @checked(old('is_active', $pool->is_active))>
                                {{ __('Active') }}
                            </label>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Pool Members') }}</p>
                                <div class="mt-2 space-y-2" data-member-list>
                                    @foreach ($pool->members as $index => $member)
                                        <div class="grid gap-2 sm:grid-cols-4 items-end">
                                            <div class="sm:col-span-2">
                                                <select name="members[{{ $index }}][user_id]" class="w-full rounded-lg border-slate-300 text-sm" required>
                                                    @foreach ($members as $user)
                                                        <option value="{{ $user->id }}" @selected($member->user_id === $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <input type="number" name="members[{{ $index }}][weight]" min="1" max="100" value="{{ $member->weight }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="{{ __('Weight') }}">
                                            </div>
                                            <label class="inline-flex items-center gap-2 text-xs text-slate-600 pb-2">
                                                <input type="hidden" name="members[{{ $index }}][is_active]" value="0">
                                                <input type="checkbox" name="members[{{ $index }}][is_active]" value="1" class="rounded border-slate-300" @checked($member->is_active)>
                                                {{ __('Active') }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-2 text-xs text-slate-500">{{ __('Weights apply to Weighted Round Robin. Inactive members never receive assignments.') }}</p>
                            </div>

                            @if (auth()->user()?->hasPermission('assignments.manage'))
                                <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                                    {{ __('Save Pool') }}
                                </button>
                            @endif
                        </form>
                    </article>
                @empty
                    <p class="text-sm text-slate-500">{{ __('No assignment pools yet.') }}</p>
                @endforelse
            </div>

            @if (auth()->user()?->hasPermission('assignments.manage'))
                <details class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-800">{{ __('Create Pool') }}</summary>
                    <form method="POST" action="{{ route('assignments.pools.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-slate-700">{{ __('Name') }}</label>
                                <input type="text" name="name" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">{{ __('Strategy') }}</label>
                                <select name="strategy" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                                    @foreach ($strategies as $key => $label)
                                        <option value="{{ $key }}">{{ __($label) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700">{{ __('Description') }}</label>
                            <textarea name="description" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" checked>
                            {{ __('Active') }}
                        </label>
                        <div class="space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Initial Members') }}</p>
                            @foreach ($members->take(5) as $index => $user)
                                <div class="grid gap-2 sm:grid-cols-4 items-end">
                                    <div class="sm:col-span-2">
                                        <select name="members[{{ $index }}][user_id]" class="w-full rounded-lg border-slate-300 text-sm">
                                            <option value="">{{ __('— Skip —') }}</option>
                                            @foreach ($members as $option)
                                                <option value="{{ $option->id }}" @selected($option->id === $user->id)>{{ $option->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <input type="number" name="members[{{ $index }}][weight]" min="1" max="100" value="1" class="w-full rounded-lg border-slate-300 text-sm">
                                    </div>
                                    <input type="hidden" name="members[{{ $index }}][is_active]" value="1">
                                </div>
                            @endforeach
                        </div>
                        <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                            {{ __('Create Pool') }}
                        </button>
                    </form>
                </details>
            @endif
        </section>

        {{-- Assignment Rules --}}
        <section class="space-y-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">{{ __('Assignment Rules') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Rules run by priority. First match wins. Default rule applies when nothing else matches.') }}</p>
            </div>

            <div class="space-y-4">
                @forelse ($rules as $rule)
                    @php
                        $conditions = $rule->conditions ?? [];
                        $metadata = $conditions['metadata'] ?? [];
                        $metadataKey = $metadata ? array_key_first($metadata) : null;
                        $metadataValue = $metadataKey ? ($metadata[$metadataKey] ?? '') : '';
                    @endphp
                    <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <form method="POST" action="{{ route('assignments.rules.update', $rule) }}" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Name') }}</label>
                                    <input type="text" name="name" value="{{ $rule->name }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Entity') }}</label>
                                    <select name="entity_type" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                                        @foreach ($entityTypes as $key => $label)
                                            <option value="{{ $key }}" @selected($rule->entity_type === $key)>{{ __($label) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Priority') }}</label>
                                    <input type="number" name="priority" min="1" max="9999" value="{{ $rule->priority }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                </div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Pool') }}</label>
                                    <select name="assignment_pool_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        <option value="">{{ __('— None —') }}</option>
                                        @foreach ($pools as $poolOption)
                                            <option value="{{ $poolOption->id }}" @selected($rule->assignment_pool_id === $poolOption->id)>{{ $poolOption->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Strategy override') }}</label>
                                    <select name="strategy" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        <option value="">{{ __('Use pool strategy') }}</option>
                                        @foreach ($strategies as $key => $label)
                                            <option value="{{ $key }}" @selected($rule->strategy === $key)>{{ __($label) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Source') }}</label>
                                    <select name="source" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        <option value="">{{ __('Any') }}</option>
                                        @foreach ($sources as $key => $label)
                                            <option value="{{ $key }}" @selected(($conditions['source'] ?? null) === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Status') }}</label>
                                    <select name="status" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        <option value="">{{ __('Any') }}</option>
                                        @foreach ($statuses as $key => $label)
                                            <option value="{{ $key }}" @selected(($conditions['status'] ?? null) === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Country') }}</label>
                                    <input type="text" name="country" value="{{ $conditions['country'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Lead Type') }}</label>
                                    <input type="text" name="lead_type" value="{{ $conditions['lead_type'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Pipeline') }}</label>
                                    <input type="text" name="pipeline" value="{{ $conditions['pipeline'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700">{{ __('Metadata Field') }}</label>
                                    <div class="mt-1 grid grid-cols-2 gap-2">
                                        <input type="text" name="metadata_key" value="{{ $metadataKey }}" placeholder="{{ __('Key') }}" class="rounded-lg border-slate-300 text-sm">
                                        <input type="text" name="metadata_value" value="{{ $metadataValue }}" placeholder="{{ __('Value') }}" class="rounded-lg border-slate-300 text-sm">
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" @checked($rule->is_active)>
                                    {{ __('Active') }}
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="is_default" value="1" class="rounded border-slate-300" @checked($rule->is_default)>
                                    {{ __('Default Rule') }}
                                </label>
                            </div>
                            @if (auth()->user()?->hasPermission('assignments.manage'))
                                <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                                    {{ __('Save Rule') }}
                                </button>
                            @endif
                        </form>
                    </article>
                @empty
                    <p class="text-sm text-slate-500">{{ __('No assignment rules yet.') }}</p>
                @endforelse
            </div>

            @if (auth()->user()?->hasPermission('assignments.manage'))
                <details class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-800">{{ __('Create Rule') }}</summary>
                    <form method="POST" action="{{ route('assignments.rules.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-700">{{ __('Name') }}</label>
                                <input type="text" name="name" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">{{ __('Entity') }}</label>
                                <select name="entity_type" class="mt-1 w-full rounded-lg border-slate-300 text-sm" required>
                                    @foreach ($entityTypes as $key => $label)
                                        <option value="{{ $key }}" @selected($key === 'lead')>{{ __($label) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">{{ __('Priority') }}</label>
                                <input type="number" name="priority" min="1" max="9999" value="100" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-slate-700">{{ __('Pool') }}</label>
                                <select name="assignment_pool_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                    <option value="">{{ __('— None —') }}</option>
                                    @foreach ($pools as $poolOption)
                                        <option value="{{ $poolOption->id }}">{{ $poolOption->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">{{ __('Strategy override') }}</label>
                                <select name="strategy" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                    <option value="">{{ __('Use pool strategy') }}</option>
                                    @foreach ($strategies as $key => $label)
                                        <option value="{{ $key }}">{{ __($label) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-700">{{ __('Source') }}</label>
                                <select name="source" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                    <option value="">{{ __('Any') }}</option>
                                    @foreach ($sources as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">{{ __('Status') }}</label>
                                <select name="status" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                    <option value="">{{ __('Any') }}</option>
                                    @foreach ($statuses as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">{{ __('Country') }}</label>
                                <input type="text" name="country" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">{{ __('Lead Type') }}</label>
                                <input type="text" name="lead_type" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">{{ __('Pipeline') }}</label>
                                <input type="text" name="pipeline" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">{{ __('Metadata Field') }}</label>
                                <div class="mt-1 grid grid-cols-2 gap-2">
                                    <input type="text" name="metadata_key" placeholder="{{ __('Key') }}" class="rounded-lg border-slate-300 text-sm">
                                    <input type="text" name="metadata_value" placeholder="{{ __('Value') }}" class="rounded-lg border-slate-300 text-sm">
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" checked>
                                {{ __('Active') }}
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="is_default" value="1" class="rounded border-slate-300">
                                {{ __('Default Rule') }}
                            </label>
                        </div>
                        <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                            {{ __('Create Rule') }}
                        </button>
                    </form>
                </details>
            @endif
        </section>
    </div>
</x-app-layout>
