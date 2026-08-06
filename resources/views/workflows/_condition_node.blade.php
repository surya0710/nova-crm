@php
    $nodeVariable = 'node'.$depth;
    $indexVariable = 'index'.$depth;
    $pathVariable = 'nodePath'.$depth;
    $parentItems = $itemsExpression;
    $currentPathExpression = $depth === 1
        ? "conditionPath('', {$indexVariable})"
        : "conditionPath(nodePath".($depth - 1).", {$indexVariable})";
@endphp

<template x-for="({{ $nodeVariable }}, {{ $indexVariable }}) in {{ $itemsExpression }}" :key="{{ $nodeVariable }}._key ?? {{ $indexVariable }}">
    <div x-data="{ get {{ $pathVariable }}() { return {{ $currentPathExpression }} } }" class="mb-3">
        <template x-if="{{ $nodeVariable }}.type === 'condition'">
            <div class="condition-row rounded-3 p-3">
                <div class="row g-2 align-items-start">
                    <div class="col-lg-3">
                        <label class="form-label small fw-semibold">Field path</label>
                        <input type="text" class="form-control form-control-sm" x-model="{{ $nodeVariable }}.field" placeholder="status or custom_fields.region" required>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label small fw-semibold">Operator</label>
                        <select class="form-select form-select-sm" x-model="{{ $nodeVariable }}.operator" @change="changeOperator({{ $nodeVariable }})">
                            <template x-for="(definition, operator) in catalog.operators" :key="operator">
                                <option :value="operator" x-text="definition.label"></option>
                            </template>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label small fw-semibold">Value</label>
                        <template x-if="operatorType({{ $nodeVariable }}.operator) === 'single'">
                            <input type="text" class="form-control form-control-sm" x-model="{{ $nodeVariable }}.value" required>
                        </template>
                        <template x-if="operatorType({{ $nodeVariable }}.operator) === 'between'">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" x-model="{{ $nodeVariable }}.value[0]" placeholder="Minimum" required>
                                <span class="input-group-text">and</span>
                                <input type="text" class="form-control" x-model="{{ $nodeVariable }}.value[1]" placeholder="Maximum" required>
                            </div>
                        </template>
                        <template x-if="operatorType({{ $nodeVariable }}.operator) === 'list'">
                            <div>
                                <template x-for="(item, valueIndex) in {{ $nodeVariable }}.value" :key="valueIndex">
                                    <div class="input-group input-group-sm mb-1">
                                        <input type="text" class="form-control" x-model="{{ $nodeVariable }}.value[valueIndex]" required>
                                        <button type="button" class="btn btn-outline-danger" @click="{{ $nodeVariable }}.value.splice(valueIndex, 1)" :disabled="{{ $nodeVariable }}.value.length === 1">Remove</button>
                                    </div>
                                </template>
                                <button type="button" class="btn btn-sm btn-link p-0" @click="addListValue({{ $nodeVariable }})">+ Add value</button>
                            </div>
                        </template>
                        <template x-if="operatorType({{ $nodeVariable }}.operator) === 'none'">
                            <div>
                                <span class="form-text">This operator does not need a value.</span>
                            </div>
                        </template>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small fw-semibold d-block">Controls</label>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" @click="move({{ $parentItems }}, {{ $indexVariable }}, -1)" :disabled="{{ $indexVariable }} === 0" title="Move up">↑</button>
                            <button type="button" class="btn btn-outline-secondary" @click="move({{ $parentItems }}, {{ $indexVariable }}, 1)" :disabled="{{ $indexVariable }} === {{ $parentItems }}.length - 1" title="Move down">↓</button>
                            <button type="button" class="btn btn-outline-danger" @click="removeAt({{ $parentItems }}, {{ $indexVariable }})" title="Remove">×</button>
                        </div>
                    </div>
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" value="1" :id="{{ $pathVariable }} + '-negated'" x-model="{{ $nodeVariable }}.negated">
                    <label class="form-check-label small" :for="{{ $pathVariable }} + '-negated'">Negate this condition</label>
                </div>
            </div>
        </template>

        <template x-if="{{ $nodeVariable }}.type === 'group'">
            <fieldset class="condition-group rounded-3 p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small fw-semibold">Match</span>
                        <select class="form-select form-select-sm w-auto" x-model="{{ $nodeVariable }}.boolean_operator">
                            <option value="all">all conditions</option>
                            <option value="any">any condition</option>
                        </select>
                        <div class="form-check ms-2">
                            <input class="form-check-input" type="checkbox" value="1" x-model="{{ $nodeVariable }}.negated">
                            <label class="form-check-label small">Negate group</label>
                        </div>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" @click="move({{ $parentItems }}, {{ $indexVariable }}, -1)" :disabled="{{ $indexVariable }} === 0">↑</button>
                        <button type="button" class="btn btn-outline-secondary" @click="move({{ $parentItems }}, {{ $indexVariable }}, 1)" :disabled="{{ $indexVariable }} === {{ $parentItems }}.length - 1">↓</button>
                        <button type="button" class="btn btn-outline-danger" @click="removeAt({{ $parentItems }}, {{ $indexVariable }})">Remove group</button>
                    </div>
                </div>

                @if ($depth < $maxDepth)
                    @include('workflows._condition_node', [
                        'depth' => $depth + 1,
                        'maxDepth' => $maxDepth,
                        'itemsExpression' => $nodeVariable.'.conditions',
                    ])
                @endif

                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addCondition({{ $nodeVariable }}.conditions)">+ Condition</button>
                    @if ($depth < $maxDepth)
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="addGroup({{ $nodeVariable }}.conditions, {{ $depth }})">+ Nested group</button>
                    @endif
                </div>
            </fieldset>
        </template>
    </div>
</template>
