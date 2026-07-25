<?php

namespace App\Services;

use App\Events\ProjectBudgetUpdated;
use App\Models\BudgetCategory;
use App\Models\BudgetItem;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BudgetService
{
    /**
     * @var list<array{name: string, slug: string, color: string, sort_order: int}>
     */
    public const DEFAULT_CATEGORIES = [
        ['name' => 'Labor', 'slug' => 'labor', 'color' => '#4f46e5', 'sort_order' => 10],
        ['name' => 'Materials', 'slug' => 'materials', 'color' => '#0ea5e9', 'sort_order' => 20],
        ['name' => 'Software', 'slug' => 'software', 'color' => '#14b8a6', 'sort_order' => 30],
        ['name' => 'Travel', 'slug' => 'travel', 'color' => '#f59e0b', 'sort_order' => 40],
        ['name' => 'Contractors', 'slug' => 'contractors', 'color' => '#a855f7', 'sort_order' => 50],
        ['name' => 'Equipment', 'slug' => 'equipment', 'color' => '#22c55e', 'sort_order' => 60],
        ['name' => 'Contingency', 'slug' => 'contingency', 'color' => '#64748b', 'sort_order' => 70],
        ['name' => 'Other', 'slug' => 'other', 'color' => '#94a3b8', 'sort_order' => 80],
    ];

    public function seedDefaultCategories(Organization|int $organization): void
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        /** @var list<array{name: string, slug: string, color: string, sort_order: int}> $categories */
        $categories = config('projects.default_budget_categories', self::DEFAULT_CATEGORIES);

        foreach ($categories as $category) {
            BudgetCategory::query()->firstOrCreate(
                [
                    'organization_id' => $organizationId,
                    'slug' => $category['slug'],
                ],
                [
                    'name' => $category['name'],
                    'color' => $category['color'],
                    'sort_order' => $category['sort_order'],
                    'is_system' => true,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $items
     */
    public function create(Project $project, array $data, array $items, User $actor): ProjectBudget
    {
        return DB::transaction(function () use ($project, $data, $items, $actor) {
            $this->seedDefaultCategories((int) $project->organization_id);

            $budget = ProjectBudget::query()->create([
                'organization_id' => $project->organization_id,
                'project_id' => $project->id,
                'name' => $data['name'] ?? __('Primary Budget'),
                'currency' => $data['currency'] ?? 'USD',
                'status' => $data['status'] ?? 'draft',
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'planned_total' => 0,
                'actual_total' => 0,
                'forecast_total' => 0,
                'variance_total' => 0,
            ]);

            $this->syncItems($budget, $items);
            $budget = $this->recalculateTotals($budget);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectBudgetUpdated::forModel(
                $budget,
                ['actor_id' => $actor->id, 'created' => true],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $this->notifyIfVarianceExceeded($budget, $project, $actor);

            return $budget->fresh(['items', 'project']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>|null  $items
     */
    public function update(ProjectBudget $budget, array $data, ?array $items, User $actor): ProjectBudget
    {
        return DB::transaction(function () use ($budget, $data, $items, $actor) {
            $payload = [];

            foreach (['name', 'currency', 'status', 'notes', 'metadata'] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            if ($payload !== []) {
                $budget->update($payload);
            }

            if ($items !== null) {
                $this->syncItems($budget, $items);
            }

            $budget = $this->recalculateTotals($budget->fresh());

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectBudgetUpdated::forModel(
                $budget,
                ['actor_id' => $actor->id, 'changes' => array_keys($payload)],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $budget->loadMissing('project');
            $this->notifyIfVarianceExceeded($budget, $budget->project, $actor);

            return $budget->fresh(['items', 'project']);
        });
    }

    public function recalculateTotals(ProjectBudget $budget): ProjectBudget
    {
        $items = $budget->items()->get();

        foreach ($items as $item) {
            $variance = round((float) $item->actual - (float) $item->planned, 2);
            if ((float) $item->variance !== $variance) {
                $item->update(['variance' => $variance]);
            }
        }

        $planned = round((float) $items->sum('planned'), 2);
        $actual = round((float) $items->sum('actual'), 2);
        $forecast = round((float) $items->sum('forecast'), 2);
        $variance = round($actual - $planned, 2);

        $budget->update([
            'planned_total' => $planned,
            'actual_total' => $actual,
            'forecast_total' => $forecast,
            'variance_total' => $variance,
        ]);

        return $budget->fresh(['items']);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    protected function syncItems(ProjectBudget $budget, array $items): void
    {
        $keepIds = [];

        foreach (array_values($items) as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages([
                    'items' => __('Each budget item requires a name.'),
                ]);
            }

            $planned = (float) ($row['planned'] ?? 0);
            $actual = (float) ($row['actual'] ?? 0);
            $forecast = (float) ($row['forecast'] ?? $planned);
            $categoryId = $row['budget_category_id'] ?? null;

            if (! empty($row['category_slug']) && ! $categoryId) {
                $categoryId = BudgetCategory::query()
                    ->where('organization_id', $budget->organization_id)
                    ->where('slug', Str::slug((string) $row['category_slug']))
                    ->value('id');
            }

            $attributes = [
                'organization_id' => $budget->organization_id,
                'project_budget_id' => $budget->id,
                'budget_category_id' => $categoryId,
                'name' => $name,
                'planned' => $planned,
                'actual' => $actual,
                'forecast' => $forecast,
                'variance' => round($actual - $planned, 2),
                'currency' => $row['currency'] ?? $budget->currency,
                'notes' => $row['notes'] ?? null,
                'sort_order' => (int) ($row['sort_order'] ?? ($index + 1) * 10),
            ];

            if (! empty($row['id'])) {
                $item = BudgetItem::query()
                    ->where('project_budget_id', $budget->id)
                    ->whereKey((int) $row['id'])
                    ->first();

                if ($item) {
                    $item->update($attributes);
                    $keepIds[] = $item->id;
                    continue;
                }
            }

            $item = BudgetItem::query()->create($attributes);
            $keepIds[] = $item->id;
        }

        $deleteQuery = BudgetItem::query()->where('project_budget_id', $budget->id);

        if ($keepIds !== []) {
            $deleteQuery->whereKeyNot($keepIds);
        }

        $deleteQuery->delete();
    }

    protected function notifyIfVarianceExceeded(ProjectBudget $budget, ?Project $project, User $actor): void
    {
        $planned = (float) $budget->planned_total;
        if ($planned <= 0) {
            return;
        }

        $threshold = (float) config('projects.budget_variance_threshold_percent', 10);
        $pct = abs((float) $budget->variance_total) / $planned * 100;

        if ($pct <= $threshold) {
            return;
        }

        $recipients = collect();
        if ($project) {
            $project->loadMissing(['owner', 'manager']);
            $recipients->push($project->owner, $project->manager);
        }

        foreach ($recipients->filter()->unique('id') as $recipient) {
            if ($recipient->id === $actor->id) {
                continue;
            }

            $recipient->notify(new CrmNotification(
                title: __('Budget variance threshold exceeded'),
                message: __('Budget ":name" variance is :pct% of planned.', [
                    'name' => $budget->name,
                    'pct' => round($pct, 1),
                ]),
                actionUrl: $project && Route::has('projects.show')
                    ? route('projects.show', $project)
                    : null,
                organizationId: (int) $budget->organization_id,
            ));
        }
    }
}
