@php
    $categories = $templates->pluck('category')->filter()->unique()->sort()->values();
    $industries = $templates->pluck('industry')->filter()->unique()->sort()->values();
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Templates')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Templates'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <form method="GET" action="{{ route('project-templates.index') }}" class="mb-6 rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <x-text-input type="search" name="search" :value="request('search')" placeholder="{{ __('Search templates…') }}" class="w-full" />
            </div>
            <div>
                <select name="category" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                    @if (request('category') && ! $categories->contains(request('category')))
                        <option value="{{ request('category') }}" selected>{{ request('category') }}</option>
                    @endif
                </select>
            </div>
            <div>
                <select name="industry" class="block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All industries') }}</option>
                    @foreach ($industries as $industry)
                        <option value="{{ $industry }}" @selected(request('industry') === $industry)>{{ $industry }}</option>
                    @endforeach
                    @if (request('industry') && ! $industries->contains(request('industry')))
                        <option value="{{ request('industry') }}" selected>{{ request('industry') }}</option>
                    @endif
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="favorites" value="1" @checked(request()->boolean('favorites')) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                    {{ __('Favorites') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="system" value="1" @checked(request()->has('system') && request()->boolean('system')) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                    {{ __('System') }}
                </label>
                <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
            </div>
        </div>
    </form>

    @if ($templates->isEmpty())
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-12 text-center text-sm text-slate-500">
            {{ __('No templates found.') }}
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($templates as $template)
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('project-templates.show', $template) }}" class="text-sm font-semibold text-slate-900 hover:text-indigo-700">
                                {{ $template->name }}
                            </a>
                            <p class="mt-1 text-xs text-slate-500 truncate">
                                {{ collect([$template->category, $template->industry])->filter()->implode(' · ') ?: __('General') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            @if ($template->is_favorite)
                                <span class="text-amber-500" title="{{ __('Favorite') }}">★</span>
                            @endif
                            @if ($template->is_system)
                                <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ __('System') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="p-5 flex-1">
                        <p class="text-sm text-slate-600 line-clamp-3">{{ $template->description ?: __('No description.') }}</p>
                        <dl class="mt-4 grid grid-cols-2 gap-3 text-xs text-slate-500">
                            <div>
                                <dt>{{ __('Used') }}</dt>
                                <dd class="text-sm font-medium text-slate-800">{{ $template->usage_count ?? 0 }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('Version') }}</dt>
                                <dd class="text-sm font-medium text-slate-800">v{{ $template->version ?? 1 }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <a href="{{ route('project-templates.show', $template) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">{{ __('View') }}</a>
                        @can('update', $template)
                            <a href="{{ route('project-templates.edit', $template) }}" class="text-sm text-slate-500 hover:text-primary-600">{{ __('Edit') }}</a>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    </x-layouts.entity-listing>
</x-app-layout>
