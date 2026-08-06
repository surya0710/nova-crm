<ul class="list-group list-group-flush">
    @foreach ($conditions as $condition)
        <li class="list-group-item px-0">
            @if ($condition->type === 'group')
                <div class="fw-semibold">
                    Match {{ strtoupper($condition->boolean_operator) }}
                    @if ($condition->negated)<span class="badge text-bg-warning">Negated</span>@endif
                </div>
                <div class="ms-3 mt-2 border-start ps-3">
                    @include('workflows._condition_summary', ['conditions' => $condition->childrenRecursive])
                </div>
            @else
                <code>{{ $condition->field }}</code>
                <span class="text-body-secondary">{{ config("workflows.operator_definitions.{$condition->operator}.label", $condition->operator) }}</span>
                @unless (in_array($condition->operator, ['empty', 'not_empty'], true))
                    <code>{{ is_array($condition->value) ? json_encode($condition->value) : $condition->value }}</code>
                @endunless
                @if ($condition->negated)<span class="badge text-bg-warning">Negated</span>@endif
            @endif
        </li>
    @endforeach
</ul>
