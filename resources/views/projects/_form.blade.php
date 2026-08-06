<div class="space-y-8">
    <div>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Project Details') }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="sm:col-span-2">
                <x-input-label for="name" :value="__('Project Name')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $project->name)" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="description" :value="__('Description')" />
                <textarea id="description" name="description" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $project->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="objective" :value="__('Objective')" />
                <textarea id="objective" name="objective" rows="2" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('objective', $project->objective) }}</textarea>
                <x-input-error :messages="$errors->get('objective')" class="mt-2" />
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Classification') }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <x-input-label for="category_id" :value="__('Category')" />
                <select id="category_id" name="category_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $project->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="project_type_id" :value="__('Project Type')" />
                <select id="project_type_id" name="project_type_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}" @selected(old('project_type_id', $project->project_type_id) == $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('project_type_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="status_id" :value="__('Status')" />
                <select id="status_id" name="status_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" @selected(old('status_id', $project->status_id) == $status->id)>{{ $status->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('status_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="lifecycle_stage_id" :value="__('Lifecycle Stage')" />
                <select id="lifecycle_stage_id" name="lifecycle_stage_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($stages as $stage)
                        <option value="{{ $stage->id }}" @selected(old('lifecycle_stage_id', $project->lifecycle_stage_id) == $stage->id)>{{ $stage->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('lifecycle_stage_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="priority" :value="__('Priority')" />
                <select id="priority" name="priority" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    @foreach (config('projects.priorities') as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', $project->priority) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('priority')" class="mt-2" />
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __('People & Client') }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <x-input-label for="client_id" :value="__('Client')" />
                <select id="client_id" name="client_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('client_id', $project->client_id) == $client->id)>
                            {{ $client->company ?: $client->name }}{{ $client->company && $client->name ? ' — ' . $client->name : '' }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="department_id" :value="__('Department')" />
                <select id="department_id" name="department_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id', $project->department_id) == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="owner_id" :value="__('Project Owner')" />
                <select id="owner_id" name="owner_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required>
                    <option value="">{{ __('Select owner…') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('owner_id', $project->owner_id) == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('owner_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="manager_id" :value="__('Project Manager')" />
                <select id="manager_id" name="manager_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required>
                    <option value="">{{ __('Select manager…') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('manager_id', $project->manager_id) == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('manager_id')" class="mt-2" />
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Schedule & Budget') }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <x-input-label for="start_date" :value="__('Start Date')" />
                <x-text-input id="start_date" class="block mt-1 w-full" type="date" name="start_date" :value="old('start_date', $project->start_date?->format('Y-m-d'))" />
                <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="planned_end_date" :value="__('Planned End Date')" />
                <x-text-input id="planned_end_date" class="block mt-1 w-full" type="date" name="planned_end_date" :value="old('planned_end_date', $project->planned_end_date?->format('Y-m-d'))" />
                <x-input-error :messages="$errors->get('planned_end_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="actual_end_date" :value="__('Actual End Date')" />
                <x-text-input id="actual_end_date" class="block mt-1 w-full" type="date" name="actual_end_date" :value="old('actual_end_date', $project->actual_end_date?->format('Y-m-d'))" />
                <x-input-error :messages="$errors->get('actual_end_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="completion_percentage" :value="__('Completion (%)')" />
                <x-text-input id="completion_percentage" class="block mt-1 w-full" type="number" name="completion_percentage" min="0" max="100" :value="old('completion_percentage', $project->completion_percentage)" />
                <x-input-error :messages="$errors->get('completion_percentage')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="estimated_budget" :value="__('Estimated Budget')" />
                <x-text-input id="estimated_budget" class="block mt-1 w-full" type="number" name="estimated_budget" step="0.01" min="0" :value="old('estimated_budget', $project->estimated_budget)" />
                <x-input-error :messages="$errors->get('estimated_budget')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="actual_budget" :value="__('Actual Budget')" />
                <x-text-input id="actual_budget" class="block mt-1 w-full" type="number" name="actual_budget" step="0.01" min="0" :value="old('actual_budget', $project->actual_budget)" />
                <x-input-error :messages="$errors->get('actual_budget')" class="mt-2" />
            </div>
        </div>
    </div>
</div>

@if (isset($metadataFields) && isset($metadataPresenter))
    @include('metadata-fields._runtime_form', [
        'metadataFields' => $metadataFields,
        'metadataPresenter' => $metadataPresenter,
        'record' => $project,
    ])
@endif
