<?php

namespace App\Services;

use App\Events\ProjectRiskCreated;
use App\Events\ProjectRiskEscalated;
use App\Events\ProjectRiskUpdated;
use App\Models\Organization;
use App\Models\ProjectRisk;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class RiskManagementService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): ProjectRisk
    {
        return DB::transaction(function () use ($data, $actor) {
            $organizationId = (int) ($data['organization_id'] ?? app(TenantContext::class)->id());
            $title = trim((string) ($data['title'] ?? ''));

            if ($title === '') {
                throw ValidationException::withMessages([
                    'title' => __('A risk title is required.'),
                ]);
            }

            $probability = $this->clampScore((int) ($data['probability'] ?? 3));
            $impact = $this->clampScore((int) ($data['impact'] ?? 3));

            $risk = ProjectRisk::query()->create([
                'organization_id' => $organizationId,
                'project_id' => $data['project_id'] ?? null,
                'portfolio_id' => $data['portfolio_id'] ?? null,
                'program_id' => $data['program_id'] ?? null,
                'title' => $title,
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? null,
                'probability' => $probability,
                'impact' => $impact,
                'severity' => $probability * $impact,
                'mitigation_plan' => $data['mitigation_plan'] ?? null,
                'contingency_plan' => $data['contingency_plan'] ?? null,
                'owner_id' => $data['owner_id'] ?? $actor->id,
                'due_date' => $data['due_date'] ?? null,
                'status' => $data['status'] ?? 'open',
                'history' => [[
                    'at' => now()->toIso8601String(),
                    'actor_id' => $actor->id,
                    'action' => 'created',
                    'status' => $data['status'] ?? 'open',
                ]],
                'metadata' => $data['metadata'] ?? null,
            ]);

            $risk = $risk->fresh(['owner', 'project']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectRiskCreated::forModel(
                $risk,
                ['actor_id' => $actor->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $risk;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectRisk $risk, array $data, User $actor): ProjectRisk
    {
        return DB::transaction(function () use ($risk, $data, $actor) {
            $previousStatus = $risk->status;
            $payload = [];

            foreach ([
                'title', 'description', 'category', 'mitigation_plan', 'contingency_plan',
                'owner_id', 'due_date', 'status', 'project_id', 'portfolio_id', 'program_id', 'metadata',
            ] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            if (array_key_exists('probability', $data)) {
                $payload['probability'] = $this->clampScore((int) $data['probability']);
            }

            if (array_key_exists('impact', $data)) {
                $payload['impact'] = $this->clampScore((int) $data['impact']);
            }

            if (isset($payload['probability']) || isset($payload['impact'])) {
                $probability = (int) ($payload['probability'] ?? $risk->probability);
                $impact = (int) ($payload['impact'] ?? $risk->impact);
                $payload['severity'] = $probability * $impact;
            }

            if ($payload !== []) {
                $risk->update($payload);
            }

            if (array_key_exists('status', $payload) && $payload['status'] !== $previousStatus) {
                $history = $risk->history ?? [];
                $history[] = [
                    'at' => now()->toIso8601String(),
                    'actor_id' => $actor->id,
                    'action' => 'status_changed',
                    'from' => $previousStatus,
                    'to' => $payload['status'],
                ];
                $risk->update(['history' => $history]);
            }

            $risk = $risk->fresh(['owner', 'project']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectRiskUpdated::forModel(
                $risk,
                ['actor_id' => $actor->id, 'changes' => array_keys($payload)],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $risk;
        });
    }

    public function delete(ProjectRisk $risk, User $actor): void
    {
        DB::transaction(function () use ($risk, $actor) {
            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectRiskUpdated::forModel(
                $risk,
                ['actor_id' => $actor->id, 'deleted' => true],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $risk->delete();
        });
    }

    public function escalate(ProjectRisk $risk, User $actor, ?string $note = null): ProjectRisk
    {
        return DB::transaction(function () use ($risk, $actor, $note) {
            $history = $risk->history ?? [];
            $history[] = [
                'at' => now()->toIso8601String(),
                'actor_id' => $actor->id,
                'action' => 'escalated',
                'note' => $note,
                'from' => $risk->status,
                'to' => 'escalated',
            ];

            $risk->update([
                'status' => 'escalated',
                'escalated_at' => now(),
                'history' => $history,
            ]);

            $risk = $risk->fresh(['owner', 'project']);

            $runtime = app(WorkflowRuntimeContext::class);
            event(ProjectRiskEscalated::forModel(
                $risk,
                ['actor_id' => $actor->id, 'note' => $note],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $this->notifyEscalation($risk, $actor);

            return $risk;
        });
    }

    /**
     * @return array{matrix: array<int, array<int, int>>, cells: list<array{probability: int, impact: int, count: int, severity: int}>}
     */
    public function matrix(Organization|int $organization, ?int $projectId = null, ?int $portfolioId = null): array
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        $query = ProjectRisk::query()
            ->where('organization_id', $organizationId)
            ->whereNotIn('status', ['closed', 'accepted']);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        if ($portfolioId) {
            $query->where('portfolio_id', $portfolioId);
        }

        $matrix = [];
        for ($p = 1; $p <= 5; $p++) {
            for ($i = 1; $i <= 5; $i++) {
                $matrix[$p][$i] = 0;
            }
        }

        $query->get(['probability', 'impact'])->each(function (ProjectRisk $risk) use (&$matrix): void {
            $p = max(1, min(5, (int) $risk->probability));
            $i = max(1, min(5, (int) $risk->impact));
            $matrix[$p][$i]++;
        });

        $cells = [];
        for ($p = 1; $p <= 5; $p++) {
            for ($i = 1; $i <= 5; $i++) {
                $cells[] = [
                    'probability' => $p,
                    'impact' => $i,
                    'count' => $matrix[$p][$i],
                    'severity' => $p * $i,
                ];
            }
        }

        return [
            'matrix' => $matrix,
            'cells' => $cells,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ProjectRisk>
     */
    public function list(Organization|int $organization, array $filters = []): Collection
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        $query = ProjectRisk::query()
            ->where('organization_id', $organizationId)
            ->with(['owner', 'project'])
            ->orderByDesc('severity')
            ->orderByDesc('id');

        if (! empty($filters['project_id'])) {
            $query->where('project_id', (int) $filters['project_id']);
        }

        if (! empty($filters['portfolio_id'])) {
            $query->where('portfolio_id', (int) $filters['portfolio_id']);
        }

        if (! empty($filters['program_id'])) {
            $query->where('program_id', (int) $filters['program_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    protected function clampScore(int $value): int
    {
        return max(1, min(5, $value));
    }

    protected function notifyEscalation(ProjectRisk $risk, User $actor): void
    {
        $recipients = collect([$risk->owner]);

        if ($risk->project_id) {
            $risk->loadMissing('project.owner', 'project.manager');
            $recipients->push($risk->project?->owner, $risk->project?->manager);
        }

        foreach ($recipients->filter()->unique('id') as $recipient) {
            if ($recipient->id === $actor->id) {
                continue;
            }

            $actionUrl = null;
            if ($risk->project_id && Route::has('projects.show')) {
                $actionUrl = route('projects.show', $risk->project);
            }

            $recipient->notify(new CrmNotification(
                title: __('Risk escalated'),
                message: __('Risk ":title" was escalated (severity :severity).', [
                    'title' => $risk->title,
                    'severity' => $risk->severity,
                ]),
                actionUrl: $actionUrl,
                organizationId: (int) $risk->organization_id,
            ));
        }
    }
}
