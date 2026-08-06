<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $program->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="code" :value="__('Code')" />
        <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" :value="old('code', $program->code)" />
        <x-input-error :messages="$errors->get('code')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" :value="__('Description')" />
        <textarea id="description" name="description" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $program->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="portfolio_id" :value="__('Portfolio')" />
        <x-text-input id="portfolio_id" class="block mt-1 w-full" type="number" name="portfolio_id" :value="old('portfolio_id', $program->portfolio_id)" />
        <x-input-error :messages="$errors->get('portfolio_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" :value="__('Status')" />
        <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
            @foreach (config('projects.program_statuses') as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $program->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="color" :value="__('Color')" />
        <div class="mt-1 flex items-center gap-3">
            <input
                id="color"
                type="color"
                name="color"
                value="{{ old('color', $program->color ?: '#4f46e5') }}"
                class="h-10 w-14 rounded border border-slate-300 bg-white p-1 cursor-pointer"
            />
            <span class="text-sm text-slate-500">{{ old('color', $program->color ?: '#4f46e5') }}</span>
        </div>
        <x-input-error :messages="$errors->get('color')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="start_date" :value="__('Start Date')" />
        <x-text-input id="start_date" class="block mt-1 w-full" type="date" name="start_date" :value="old('start_date', $program->start_date?->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="target_end_date" :value="__('Target End Date')" />
        <x-text-input id="target_end_date" class="block mt-1 w-full" type="date" name="target_end_date" :value="old('target_end_date', $program->target_end_date?->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('target_end_date')" class="mt-2" />
    </div>
</div>
