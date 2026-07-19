window.workflowBuilder = function workflowBuilder(initial) {
    return {
        catalog: initial.catalog,
        members: initial.members,
        triggerType: initial.triggerType,
        conditions: initial.conditions ?? [],
        actions: initial.actions ?? [],
        nextKey: 1,

        init() {
            this.conditions = this.conditions.map((node) => this.normalizeNode(node));
            this.actions = this.actions.map((action) => this.normalizeAction(action));
            if (this.actions.length === 0) {
                this.addAction();
            }
        },

        payloadMarker() {
            try {
                this.cleanActions();
                return 'workflow-builder-v1';
            } catch (error) {
                return `invalid:${error.message}`;
            }
        },

        conditionsJson() {
            return JSON.stringify(this.conditions.map((node) => this.cleanNode(node)));
        },

        actionsJson() {
            try {
                return JSON.stringify(this.cleanActions());
            } catch (error) {
                return JSON.stringify({ invalid: error.message });
            }
        },

        cleanNode(node) {
            if (node.type === 'group') {
                return {
                    type: 'group',
                    boolean_operator: node.boolean_operator,
                    negated: Boolean(node.negated),
                    conditions: node.conditions.map((child) => this.cleanNode(child)),
                };
            }

            return {
                type: 'condition',
                field: node.field,
                operator: node.operator,
                value: this.operatorType(node.operator) === 'none' ? null : node.value,
                negated: Boolean(node.negated),
            };
        },

        cleanActions() {
            return this.actions.map((action, position) => {
                const configuration = {};
                const fields = this.catalog.actions[action.type]?.form_fields ?? {};
                Object.entries(fields).forEach(([fieldName, field]) => {
                    if (field.type === 'key_value') {
                        configuration[fieldName] = this.pairsToMap(action.pairs[fieldName] ?? []);
                    } else if (action.configuration[fieldName] !== undefined) {
                        configuration[fieldName] = action.configuration[fieldName];
                    }
                });

                return {
                    type: action.type,
                    name: action.name || null,
                    status: action.status,
                    position,
                    configuration,
                };
            });
        },

        pairsToMap(pairs) {
            const values = Object.create(null);
            for (const pair of pairs) {
                const key = String(pair.key ?? '').trim();
                if (!/^[A-Za-z][A-Za-z0-9_.-]{0,127}$/.test(key)
                    || ['__proto__', 'prototype', 'constructor'].includes(key)) {
                    throw new Error(`Invalid configuration key: ${key || '(empty)'}`);
                }
                if (Object.prototype.hasOwnProperty.call(values, key)) {
                    throw new Error(`Duplicate configuration key: ${key}`);
                }
                values[key] = pair.value;
            }
            return values;
        },

        normalizeNode(node) {
            node = node && typeof node === 'object' ? node : {};
            if (node.type === 'group') {
                return {
                    _key: `condition-${this.nextKey++}`,
                    type: 'group',
                    boolean_operator: node.boolean_operator ?? 'all',
                    negated: Boolean(node.negated),
                    conditions: (Array.isArray(node.conditions) ? node.conditions : []).map((child) => this.normalizeNode(child)),
                };
            }

            const operator = node.operator ?? 'equals';
            let value = node.value ?? '';
            if (this.operatorType(operator) === 'between') {
                value = Array.isArray(value) ? [value[0] ?? '', value[1] ?? ''] : ['', ''];
            }
            if (this.operatorType(operator) === 'list') {
                value = Array.isArray(value) ? value : String(value ?? '').split(',').map((item) => item.trim()).filter(Boolean);
                if (value.length === 0) value = [''];
            }

            return {
                _key: `condition-${this.nextKey++}`,
                type: 'condition',
                field: node.field ?? '',
                operator,
                value,
                negated: Boolean(node.negated),
            };
        },

        normalizeAction(action) {
            action = action && typeof action === 'object' ? action : {};
            const normalized = {
                _key: `action-${this.nextKey++}`,
                type: action.type ?? Object.keys(this.catalog.actions)[0],
                name: action.name ?? '',
                status: action.status ?? 'active',
                configuration: action.configuration && typeof action.configuration === 'object' ? action.configuration : {},
                pairs: {},
            };

            this.prepareActionFields(normalized);
            return normalized;
        },

        prepareActionFields(action) {
            const fields = this.catalog.actions[action.type]?.form_fields ?? {};
            Object.entries(fields).forEach(([key, field]) => {
                if (field.type === 'key_value') {
                    const values = action.configuration[key] ?? {};
                    action.pairs[key] = Object.entries(values).map(([pairKey, value]) => ({ key: pairKey, value }));
                    if (action.pairs[key].length === 0 && field.required) action.pairs[key].push({ key: '', value: '' });
                } else if (action.configuration[key] === undefined) {
                    action.configuration[key] = field.type === 'select' ? Object.keys(field.options ?? {})[0] ?? '' : '';
                }
            });
        },

        changeActionType(action) {
            action.configuration = {};
            action.pairs = {};
            this.prepareActionFields(action);
        },

        changeOperator(node) {
            const valueType = this.operatorType(node.operator);
            if (valueType === 'between') node.value = ['', ''];
            else if (valueType === 'list') node.value = [''];
            else if (valueType === 'none') node.value = null;
            else if (Array.isArray(node.value) || node.value === null) node.value = '';
        },

        operatorType(operator) {
            return this.catalog.operators[operator]?.value_type ?? 'single';
        },

        addCondition(target) {
            target.push(this.normalizeNode({ type: 'condition' }));
        },

        addGroup(target, depth) {
            if (depth >= this.catalog.maxDepth) return;
            target.push(this.normalizeNode({
                type: 'group',
                boolean_operator: 'all',
                conditions: [{ type: 'condition' }],
            }));
        },

        addAction() {
            this.actions.push(this.normalizeAction({}));
        },

        removeAt(items, index) {
            items.splice(index, 1);
        },

        move(items, index, direction) {
            const destination = index + direction;
            if (destination < 0 || destination >= items.length) return;
            [items[index], items[destination]] = [items[destination], items[index]];
        },

        addListValue(node) {
            node.value.push('');
        },

        addPair(action, field) {
            action.pairs[field].push({ key: '', value: '' });
        },

        conditionPath(parentPath, index) {
            return parentPath ? `${parentPath}[conditions][${index}]` : `conditions[${index}]`;
        },

        triggerEntity() {
            return this.catalog.triggers[this.triggerType]?.entity ?? null;
        },

        actionCompatible(type) {
            return (this.catalog.actions[type]?.entities ?? []).includes(this.triggerEntity());
        },
    };
};
