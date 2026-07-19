@include('workflows._assets')

@php
    $conditionToArray = function ($condition) use (&$conditionToArray) {
        return [
            'type' => $condition->type,
            'boolean_operator' => $condition->boolean_operator,
            'field' => $condition->field,
            'operator' => $condition->operator,
            'value' => $condition->value,
            'negated' => $condition->negated,
            'conditions' => $condition->childrenRecursive->map($conditionToArray)->values()->all(),
        ];
    };

    $storedConditions = $workflow->exists
        ? $workflow->rootConditions->map($conditionToArray)->values()->all()
        : [];
    $storedActions = $workflow->exists
        ? $workflow->actions->map->only(['type', 'name', 'configuration', 'status'])->values()->all()
        : [];
    $builderData = [
        'catalog' => $catalog,
        'members' => $members->map->only(['id', 'name'])->values()->all(),
        'triggerType' => old('trigger_type', $workflow->trigger_type ?? array_key_first($catalog['triggers'])),
        'conditions' => (array) old('conditions', $storedConditions),
        'actions' => (array) old('actions', $storedActions),
    ];
@endphp

<div class="workflow-bootstrap" x-data="workflowBuilder(@js($builderData))" x-init="init()">
    <input type="hidden" name="workflow_payload_complete" :value="payloadMarker()">
    <input type="hidden" name="trigger_config_json" value="{}">
    <input type="hidden" name="conditions_json" :value="conditionsJson()">
    <input type="hidden" name="actions_json" :value="actionsJson()">

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <h2 class="h6 alert-heading">Please correct the workflow definition.</h2>
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card workflow-card mb-4">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-1">Workflow details</h2>
            <p class="text-body-secondary small mb-0">Name the workflow and choose the exact event that starts it.</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="workflow-name" class="form-label">Name</label>
                    <input id="workflow-name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $workflow->name) }}" maxlength="255" required autofocus>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="workflow-trigger" class="form-label">Trigger</label>
                    <select id="workflow-trigger" name="trigger_type" class="form-select @error('trigger_type') is-invalid @enderror" x-model="triggerType" required>
                        @foreach ($catalog['triggers'] as $value => $definition)
                            <option value="{{ $value }}">{{ $definition['label'] }}</option>
                        @endforeach
                    </select>
                    @error('trigger_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <p class="form-text mb-0" x-text="catalog.triggers[triggerType]?.description"></p>
                </div>
                <div class="col-12">
                    <label for="workflow-description" class="form-label">Description</label>
                    <textarea id="workflow-description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" maxlength="5000">{{ old('description', $workflow->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="workflow-concurrency" class="form-label">Concurrency limit</label>
                    <input id="workflow-concurrency" name="concurrency_limit" type="number" class="form-control" min="1" max="100" value="{{ old('concurrency_limit', $workflow->concurrency_limit ?? 1) }}" required>
                </div>
                <div class="col-md-6">
                    <label for="workflow-timeout" class="form-label">Execution timeout (seconds)</label>
                    <input id="workflow-timeout" name="execution_timeout_seconds" type="number" class="form-control" min="1" max="300" value="{{ old('execution_timeout_seconds', $workflow->execution_timeout_seconds ?? 300) }}" required>
                </div>
            </div>

            <p class="form-text mt-3 mb-0">Phase 9.2 trigger filtering is expressed with conditions. Advanced trigger configuration is not available.</p>
        </div>
    </div>

    <div class="card workflow-card mb-4">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-1">Conditions</h2>
                <p class="text-body-secondary small mb-0">Use all/any groups and nest groups up to {{ $catalog['maxDepth'] }} levels.</p>
            </div>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-primary" @click="addCondition(conditions)">+ Condition</button>
                <button type="button" class="btn btn-outline-secondary" @click="addGroup(conditions, 0)">+ Group</button>
            </div>
        </div>
        <div class="card-body">
            <div x-show="conditions.length === 0" class="text-center text-body-secondary py-4">
                No conditions. Every matching trigger will run this workflow.
            </div>
            @include('workflows._condition_node', [
                'depth' => 1,
                'maxDepth' => $catalog['maxDepth'],
                'itemsExpression' => 'conditions',
            ])
        </div>
    </div>

    <div class="card workflow-card">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-1">Actions</h2>
                <p class="text-body-secondary small mb-0">Actions run sequentially from top to bottom.</p>
            </div>
            <button type="button" class="btn btn-sm btn-primary" @click="addAction()">+ Add action</button>
        </div>
        <div class="card-body">
            <template x-for="(action, actionIndex) in actions" :key="action._key">
                <div class="border rounded-3 p-3 mb-3">
                    <div class="row g-3">
                        <div class="col-lg-5">
                            <label class="form-label small fw-semibold">Action type</label>
                            <select class="form-select" x-model="action.type" @change="changeActionType(action)">
                                <template x-for="(definition, type) in catalog.actions" :key="type">
                                    <option :value="type" :disabled="!(definition.entities ?? []).includes(triggerEntity())" x-text="definition.label"></option>
                                </template>
                            </select>
                            <p class="form-text mb-0" x-text="catalog.actions[action.type]?.description"></p>
                            <p class="small text-warning mb-0" x-show="!actionCompatible(action.type)">This action does not support the selected trigger entity.</p>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label small fw-semibold">Step name <span class="fw-normal text-body-secondary">(optional)</span></label>
                            <input class="form-control" type="text" x-model="action.name" maxlength="255">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label small fw-semibold">Status</label>
                            <select class="form-select" x-model="action.status">
                                <option value="active">Active</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <template x-for="(field, fieldName) in catalog.actions[action.type]?.form_fields ?? {}" :key="fieldName">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold" x-text="field.label"></label>

                                <template x-if="field.type === 'textarea'">
                                    <textarea class="form-control" rows="3" x-model="action.configuration[fieldName]" :required="field.required"></textarea>
                                </template>
                                <template x-if="field.type === 'user'">
                                    <select class="form-select" x-model="action.configuration[fieldName]" :required="field.required">
                                        <option value="">Select a member</option>
                                        <template x-for="member in members" :key="member.id">
                                            <option :value="member.id" x-text="member.name"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="field.type === 'select'">
                                    <select class="form-select" x-model="action.configuration[fieldName]" :required="field.required">
                                        <template x-for="(label, value) in field.options" :key="value">
                                            <option :value="value" x-text="label"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="field.type === 'key_value'">
                                    <div>
                                        <template x-for="(pair, pairIndex) in action.pairs[fieldName]" :key="pairIndex">
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" x-model="pair.key" placeholder="Key" :required="field.required">
                                                <input type="text" class="form-control" x-model="pair.value" placeholder="Value" :required="field.required">
                                                <button type="button" class="btn btn-outline-danger" @click="removeAt(action.pairs[fieldName], pairIndex)">×</button>
                                            </div>
                                        </template>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="addPair(action, fieldName)">+ Add value</button>
                                    </div>
                                </template>
                                <template x-if="!['textarea', 'user', 'select', 'key_value'].includes(field.type)">
                                    <input class="form-control" :type="field.type ?? 'text'" x-model="action.configuration[fieldName]" :required="field.required">
                                </template>
                            </div>
                        </template>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" @click="move(actions, actionIndex, -1)" :disabled="actionIndex === 0">Move up</button>
                            <button type="button" class="btn btn-outline-secondary" @click="move(actions, actionIndex, 1)" :disabled="actionIndex === actions.length - 1">Move down</button>
                            <button type="button" class="btn btn-outline-danger" @click="removeAt(actions, actionIndex)" :disabled="actions.length === 1">Remove</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
