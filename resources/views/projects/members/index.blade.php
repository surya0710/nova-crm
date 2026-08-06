<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Members')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Members'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    @can('assignMembers', $project)
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-3">{{ __('Add Member') }}</h3>
            <form method="POST" action="{{ route('projects.members.store', $project) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @csrf
                <select name="user_id" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" required>
                    <option value="">{{ __('Select user…') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                <select name="project_role" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" required>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(old('project_role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-primary-button type="submit">{{ __('Add Member') }}</x-primary-button>
            </form>
            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
            <x-input-error :messages="$errors->get('project_role')" class="mt-2" />
        </div>
    @endcan

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($members->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No members assigned yet.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Member') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Role') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Joined') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($members as $member)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $member->user?->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $member->project_role_label }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $member->joined_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $member->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $member->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @can('delete', $member)
                                        <form method="POST" action="{{ route('projects.members.destroy', [$project, $member]) }}" class="inline" onsubmit="return confirm('{{ __('Remove this member?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
