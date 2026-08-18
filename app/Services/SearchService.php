<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\PortfolioReport;
use App\Models\Product;
use App\Models\Program;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Models\ProjectBaseline;
use App\Models\ProjectBudget;
use App\Models\ProjectIssue;
use App\Models\ProjectLabel;
use App\Models\ProjectMention;
use App\Models\ProjectReport;
use App\Models\ProjectRisk;
use App\Models\ProjectTemplate;
use App\Models\Quotation;
use App\Models\ResourceAllocation;
use App\Models\SavedFilter;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class SearchService
{
    public function __construct(
        protected MetadataSearchService $metadataSearch,
    ) {}

    /**
     * @return Collection<int, array{type: string, label: string, title: string, subtitle: string|null, url: string}>
     */
    public function search(User $user, string $query, int $limit = 20): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        $results = collect();

        if ($user->hasPermission('leads.view')) {
            $results = $results->merge($this->searchLeads($query));
        }

        if ($user->hasPermission('customers.view')) {
            $results = $results->merge($this->searchCustomers($query));
        }

        if ($user->hasPermission('opportunities.view')) {
            $results = $results->merge($this->searchOpportunities($query));
        }

        if ($user->hasPermission('products.view')) {
            $results = $results->merge($this->searchProducts($query));
        }

        if ($user->hasPermission('quotations.view')) {
            $results = $results->merge($this->searchQuotations($query));
        }

        if ($user->hasPermission('invoices.view')) {
            $results = $results->merge($this->searchInvoices($query));
        }

        if ($user->hasPermission('payments.view')) {
            $results = $results->merge($this->searchPayments($query));
        }

        if ($user->hasPermission('projects.view')) {
            $results = $results->merge($this->searchProjects($query));
        }

        if ($user->hasPermission('projects.progress.view')) {
            $results = $results->merge($this->searchProgressUpdates($query));
        }

        if ($user->hasPermission('projects.reports.view')) {
            $results = $results->merge($this->searchProjectReports($query));
        }

        if ($user->hasPermission('tasks.view')) {
            $results = $results->merge($this->searchTasks($query));
        }

        if ($user->hasPermission('resources.view')) {
            $results = $results->merge($this->searchResourceAllocations($query));
        }

        if ($user->hasPermission('projects.labels.view') || $user->hasPermission('projects.labels.manage')) {
            $results = $results->merge($this->searchProjectLabels($query));
        }

        if ($user->hasPermission('projects.templates.view') || $user->hasPermission('projects.templates.manage')) {
            $results = $results->merge($this->searchProjectTemplates($query));
        }

        if ($user->hasPermission('projects.mentions.view')) {
            $results = $results->merge($this->searchProjectMentions($query));
        }

        if ($user->hasPermission('projects.collaboration.view') || $user->hasPermission('projects.collaboration.manage')) {
            $results = $results->merge($this->searchTaskComments($query));
        }

        if ($user->hasPermission('projects.portfolios.view') || $user->hasPermission('projects.portfolios.manage')) {
            $results = $results->merge($this->searchPortfolios($query));
        }

        if ($user->hasPermission('projects.programs.view') || $user->hasPermission('projects.programs.manage')) {
            $results = $results->merge($this->searchPrograms($query));
        }

        if ($user->hasPermission('projects.risks.view') || $user->hasPermission('projects.risks.manage')) {
            $results = $results->merge($this->searchProjectRisks($query));
        }

        if ($user->hasPermission('projects.issues.view') || $user->hasPermission('projects.issues.manage')) {
            $results = $results->merge($this->searchProjectIssues($query));
        }

        if ($user->hasPermission('projects.baselines.view') || $user->hasPermission('projects.baselines.manage')) {
            $results = $results->merge($this->searchProjectBaselines($query));
        }

        if ($user->hasPermission('projects.budgets.view') || $user->hasPermission('projects.budgets.manage')) {
            $results = $results->merge($this->searchProjectBudgets($query));
        }

        if ($user->hasPermission('projects.portfolio_reports.view') || $user->hasPermission('projects.portfolio_reports.generate')) {
            $results = $results->merge($this->searchPortfolioReports($query));
        }

        return $results->take($limit)->values();
    }

    protected function searchLeads(string $query): Collection
    {
        return Lead::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('company', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");

                $this->metadataSearch->applySearchConstraint($q, 'lead', $query);
            })
            ->limit(5)
            ->get()
            ->map(fn (Lead $lead) => [
                'type' => crm_term('lead'),
                'label' => crm_term('leads'),
                'title' => $lead->name,
                'subtitle' => $lead->company,
                'url' => route('leads.show', $lead),
            ]);
    }

    protected function searchCustomers(string $query): Collection
    {
        return Customer::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('company', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");

                $this->metadataSearch->applySearchConstraint($q, 'customer', $query);
            })
            ->limit(5)
            ->get()
            ->map(fn (Customer $customer) => [
                'type' => crm_term('customer'),
                'label' => crm_term('customers'),
                'title' => $customer->display_name,
                'subtitle' => $customer->email,
                'url' => route('customers.show', $customer),
            ]);
    }

    protected function searchOpportunities(string $query): Collection
    {
        return Opportunity::query()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%");

                $this->metadataSearch->applySearchConstraint($q, 'opportunity', $query);
            })
            ->limit(5)
            ->get()
            ->map(fn (Opportunity $opportunity) => [
                'type' => crm_term('deal'),
                'label' => crm_term('pipeline'),
                'title' => $opportunity->title,
                'subtitle' => $opportunity->stage,
                'url' => route('pipeline.show', $opportunity),
            ]);
    }

    protected function searchProducts(string $query): Collection
    {
        return Product::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('hsn_sac', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(fn (Product $product) => [
                'type' => crm_term('product'),
                'label' => crm_term('products'),
                'title' => $product->name,
                'subtitle' => $product->sku,
                'url' => route('products.show', $product),
            ]);
    }

    public function searchQuotations(string $query): Collection
    {
        return Quotation::query()
            ->where(function ($q) use ($query) {
                $q->where('number', 'like', "%{$query}%")
                    ->orWhere('title', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(fn (Quotation $quotation) => [
                'type' => crm_term('quotation'),
                'label' => crm_term('quotations'),
                'title' => $quotation->number,
                'subtitle' => $quotation->title,
                'url' => route('quotations.show', $quotation),
                'workspace' => 'crm',
            ]);
    }

    public function searchInvoices(string $query): Collection
    {
        return Invoice::query()
            ->where(function ($q) use ($query) {
                $q->where('number', 'like', "%{$query}%")
                    ->orWhere('title', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(fn (Invoice $invoice) => [
                'type' => crm_term('invoice'),
                'label' => crm_term('invoices'),
                'title' => $invoice->number,
                'subtitle' => $invoice->title,
                'url' => route('invoices.show', $invoice),
                'workspace' => 'crm',
            ]);
    }

    public function searchPayments(string $query): Collection
    {
        return Payment::query()
            ->where(function ($q) use ($query) {
                $q->where('number', 'like', "%{$query}%")
                    ->orWhere('reference', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(fn (Payment $payment) => [
                'type' => crm_term('payment'),
                'label' => crm_term('payments'),
                'title' => $payment->number,
                'subtitle' => $payment->formatted_amount,
                'url' => route('payments.show', $payment),
                'workspace' => 'crm',
            ]);
    }

    public function searchSavedViews(User $user, string $query, int $limit = 5): Collection
    {
        if (! $user->hasAnyPermission(['leads.view', 'customers.view', 'opportunities.view'])) {
            return collect();
        }

        return SavedFilter::query()
            ->where('name', 'like', "%{$query}%")
            ->whereIn('entity_type', ['lead', 'customer', 'opportunity'])
            ->limit($limit)
            ->get()
            ->map(function (SavedFilter $filter) {
                $href = match ($filter->entity_type) {
                    'lead' => route('leads.index', ['saved_filter' => $filter->id]),
                    'customer' => route('customers.index', ['saved_filter' => $filter->id]),
                    default => route('pipeline.index', ['saved_filter' => $filter->id]),
                };

                return [
                    'type' => __('Saved view'),
                    'label' => __('Saved Views'),
                    'title' => $filter->name,
                    'subtitle' => $filter->entity_type,
                    'url' => $href,
                    'workspace' => 'crm',
                ];
            });
    }

    public function searchCrmActivities(User $user, string $query, int $limit = 5): Collection
    {
        if (! $user->hasPermission('leads.view')) {
            return collect();
        }

        return Lead::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('next_follow_up_note', 'like', "%{$query}%")
                    ->orWhereHas('notes', fn ($n) => $n->where('body', 'like', "%{$query}%"));
            })
            ->where(function ($q) {
                $q->whereNotNull('next_follow_up_at')
                    ->orWhereHas('notes');
            })
            ->limit($limit)
            ->get()
            ->map(fn (Lead $lead) => [
                'type' => __('Activity'),
                'label' => __('Activities'),
                'title' => $lead->name,
                'subtitle' => $lead->next_follow_up_note ?: $lead->status_label,
                'url' => route('leads.show', $lead),
                'workspace' => 'crm',
            ]);
    }

    protected function searchProjects(string $query): Collection
    {
        return Project::query()
            ->with(['client', 'owner', 'manager'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('project_number', 'like', "%{$query}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$query}%"))
                    ->orWhereHas('owner', fn ($o) => $o->where('name', 'like', "%{$query}%"))
                    ->orWhereHas('manager', fn ($m) => $m->where('name', 'like', "%{$query}%"));

                $this->metadataSearch->applySearchConstraint($q, 'project', $query);
            })
            ->limit(5)
            ->get()
            ->map(fn (Project $project) => [
                'type' => __('Project'),
                'label' => __('Projects'),
                'title' => $project->name,
                'subtitle' => $project->project_number,
                'url' => route('projects.show', $project),
            ]);
    }

    protected function searchTasks(string $query): Collection
    {
        return Task::query()
            ->with(['assignee', 'project'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('task_number', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereHas('assignee', fn ($a) => $a->where('name', 'like', "%{$query}%"))
                    ->orWhereHas('project', fn ($p) => $p->where('name', 'like', "%{$query}%"));

                $this->metadataSearch->applySearchConstraint($q, 'task', $query);
            })
            ->limit(5)
            ->get()
            ->map(fn (Task $task) => [
                'type' => __('Task'),
                'label' => __('Tasks'),
                'title' => $task->title,
                'subtitle' => $task->task_number,
                'url' => route('tasks.show', $task),
            ]);
    }

    protected function searchResourceAllocations(string $query): Collection
    {
        return ResourceAllocation::query()
            ->with(['employee', 'project'])
            ->where(function ($q) use ($query) {
                $q->where('notes', 'like', "%{$query}%")
                    ->orWhereHas('employee', fn ($e) => $e
                        ->where('first_name', 'like', "%{$query}%")
                        ->orWhere('last_name', 'like', "%{$query}%"))
                    ->orWhereHas('project', fn ($p) => $p->where('name', 'like', "%{$query}%"));

                $this->metadataSearch->applySearchConstraint($q, 'resource_allocation', $query);
            })
            ->limit(5)
            ->get()
            ->map(fn (ResourceAllocation $allocation) => [
                'type' => __('Allocation'),
                'label' => __('Resource Allocations'),
                'title' => $allocation->employee?->full_name
                    ?? __('Allocation #:id', ['id' => $allocation->id]),
                'subtitle' => $allocation->project?->name
                    ?? $allocation->allocation_type_label,
                'url' => route('resources.allocations.show', $allocation),
            ]);
    }

    protected function searchProgressUpdates(string $query): Collection
    {
        return ProgressUpdate::query()
            ->with(['project', 'milestone'])
            ->where(function ($q) use ($query) {
                $q->where('summary', 'like', "%{$query}%")
                    ->orWhere('blockers', 'like', "%{$query}%")
                    ->orWhere('next_steps', 'like', "%{$query}%")
                    ->orWhereHas('project', fn ($p) => $p->where('name', 'like', "%{$query}%"))
                    ->orWhereHas('milestone', fn ($m) => $m->where('name', 'like', "%{$query}%"));

                $this->metadataSearch->applySearchConstraint($q, 'project_progress_update', $query);
            })
            ->limit(5)
            ->get()
            ->map(fn (ProgressUpdate $update) => [
                'type' => __('Progress Update'),
                'label' => __('Progress Updates'),
                'title' => $update->summary,
                'subtitle' => collect([
                    $update->project?->name,
                    $update->milestone?->name,
                ])->filter()->implode(' · ') ?: null,
                'url' => $update->project
                    ? route('projects.progress.index', $update->project)
                    : route('projects.index'),
            ]);
    }

    protected function searchProjectReports(string $query): Collection
    {
        return ProjectReport::query()
            ->with('project')
            ->where(function ($q) use ($query) {
                $q->where('report_type', 'like', "%{$query}%")
                    ->orWhereHas('project', fn ($p) => $p->where('name', 'like', "%{$query}%"));
            })
            ->limit(5)
            ->get()
            ->map(fn (ProjectReport $report) => [
                'type' => __('Report'),
                'label' => __('Project Reports'),
                'title' => $report->report_type_label,
                'subtitle' => $report->project?->name,
                'url' => $report->project
                    ? route('projects.reports.index', $report->project)
                    : route('projects.executive'),
            ]);
    }

    protected function searchProjectLabels(string $query): Collection
    {
        return ProjectLabel::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (ProjectLabel $label) => [
                'type' => __('Label'),
                'label' => __('Project Labels'),
                'title' => $label->name,
                'subtitle' => $label->description
                    ? Str::limit($label->description, 80)
                    : null,
                'url' => route('project-labels.index', ['search' => $label->name]),
            ]);
    }

    protected function searchProjectTemplates(string $query): Collection
    {
        $organizationId = app(TenantContext::class)->id();

        return ProjectTemplate::query()
            ->withoutGlobalScopes()
            ->where(function (Builder $scope) use ($organizationId) {
                if ($organizationId) {
                    $scope->where('organization_id', $organizationId)
                        ->orWhere(function (Builder $system) {
                            $system->whereNull('organization_id')->where('is_system', true);
                        });
                } else {
                    $scope->whereNull('organization_id')->where('is_system', true);
                }
            })
            ->where(function (Builder $q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('slug', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (ProjectTemplate $template) => [
                'type' => __('Template'),
                'label' => __('Project Templates'),
                'title' => $template->name,
                'subtitle' => $template->slug,
                'url' => route('project-templates.show', $template),
            ]);
    }

    protected function searchProjectMentions(string $query): Collection
    {
        return ProjectMention::query()
            ->with(['project', 'mentionedBy'])
            ->where(function ($q) use ($query) {
                $q->where('excerpt', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(fn (ProjectMention $mention) => [
                'type' => __('Mention'),
                'label' => __('Mentions'),
                'title' => Str::limit((string) ($mention->excerpt ?? ''), 80) ?: __('Mention'),
                'subtitle' => collect([
                    $mention->project?->name,
                    $mention->mentionedBy?->name,
                ])->filter()->implode(' · ') ?: null,
                'url' => $mention->project
                    ? route('projects.collaboration.show', $mention->project)
                    : route('mentions.index'),
            ]);
    }

    protected function searchTaskComments(string $query): Collection
    {
        return TaskComment::query()
            ->with(['task.project', 'user'])
            ->where(function ($q) use ($query) {
                $q->where('comment', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function (TaskComment $comment) {
                $project = $comment->task?->project;

                return [
                    'type' => __('Comment'),
                    'label' => __('Discussion'),
                    'title' => Str::limit((string) $comment->comment, 80),
                    'subtitle' => collect([
                        $comment->task?->title,
                        $comment->user?->name,
                    ])->filter()->implode(' · ') ?: null,
                    'url' => $project
                        ? route('projects.collaboration.show', $project)
                        : ($comment->task
                            ? route('tasks.show', $comment->task)
                            : route('projects.index')),
                ];
            });
    }

    protected function searchPortfolios(string $query): Collection
    {
        return Portfolio::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (Portfolio $portfolio) => [
                'type' => __('Portfolio'),
                'label' => __('Portfolios'),
                'title' => $portfolio->name,
                'subtitle' => collect([$portfolio->code, $portfolio->status])->filter()->implode(' · ') ?: null,
                'url' => $this->safeRoute('portfolios.show', $portfolio, 'portfolios.index'),
            ]);
    }

    protected function searchPrograms(string $query): Collection
    {
        return Program::query()
            ->with('portfolio:id,name,code')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (Program $program) => [
                'type' => __('Program'),
                'label' => __('Programs'),
                'title' => $program->name,
                'subtitle' => collect([
                    $program->code,
                    $program->portfolio?->name,
                    $program->status,
                ])->filter()->implode(' · ') ?: null,
                'url' => $this->safeRoute('programs.show', $program, 'programs.index'),
            ]);
    }

    protected function searchProjectRisks(string $query): Collection
    {
        return ProjectRisk::query()
            ->with(['project:id,name', 'portfolio:id,name'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('category', 'like', "%{$query}%");
            })
            ->orderByDesc('severity')
            ->limit(5)
            ->get()
            ->map(fn (ProjectRisk $risk) => [
                'type' => __('Risk'),
                'label' => __('Project Risks'),
                'title' => $risk->title,
                'subtitle' => collect([
                    $risk->project?->name ?? $risk->portfolio?->name,
                    $risk->status,
                    $risk->severity ? __('Severity :n', ['n' => $risk->severity]) : null,
                ])->filter()->implode(' · ') ?: null,
                'url' => $this->safeRoute('project-risks.show', $risk, 'project-risks.index'),
            ]);
    }

    protected function searchProjectIssues(string $query): Collection
    {
        return ProjectIssue::query()
            ->with(['project:id,name', 'portfolio:id,name'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (ProjectIssue $issue) => [
                'type' => __('Issue'),
                'label' => __('Project Issues'),
                'title' => $issue->title,
                'subtitle' => collect([
                    $issue->project?->name ?? $issue->portfolio?->name,
                    $issue->priority,
                    $issue->status,
                ])->filter()->implode(' · ') ?: null,
                'url' => $this->safeRoute('project-issues.show', $issue, 'project-issues.index'),
            ]);
    }

    protected function searchProjectBaselines(string $query): Collection
    {
        return ProjectBaseline::query()
            ->with(['project:id,name,slug'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('notes', 'like', "%{$query}%")
                    ->orWhereHas('project', fn ($p) => $p->where('name', 'like', "%{$query}%"));
            })
            ->orderByDesc('version')
            ->limit(5)
            ->get()
            ->map(fn (ProjectBaseline $baseline) => [
                'type' => __('Baseline'),
                'label' => __('Project Baselines'),
                'title' => $baseline->name ?: __('Baseline v:version', ['version' => $baseline->version]),
                'subtitle' => collect([
                    $baseline->project?->name,
                    __('v:version', ['version' => $baseline->version]),
                ])->filter()->implode(' · ') ?: null,
                'url' => $this->safeRoute('project-baselines.show', $baseline, 'project-baselines.index'),
            ]);
    }

    protected function searchProjectBudgets(string $query): Collection
    {
        return ProjectBudget::query()
            ->with(['project:id,name,slug'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('notes', 'like', "%{$query}%")
                    ->orWhereHas('project', fn ($p) => $p->where('name', 'like', "%{$query}%"));
            })
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (ProjectBudget $budget) => [
                'type' => __('Budget'),
                'label' => __('Project Budgets'),
                'title' => $budget->name,
                'subtitle' => collect([
                    $budget->project?->name,
                    $budget->status,
                    $budget->currency,
                ])->filter()->implode(' · ') ?: null,
                'url' => $this->safeRoute('project-budgets.show', $budget, 'project-budgets.index'),
            ]);
    }

    protected function searchPortfolioReports(string $query): Collection
    {
        return PortfolioReport::query()
            ->with(['portfolio:id,name', 'program:id,name'])
            ->where(function ($q) use ($query) {
                $q->where('report_type', 'like', "%{$query}%")
                    ->orWhere('format', 'like', "%{$query}%")
                    ->orWhereHas('portfolio', fn ($p) => $p->where('name', 'like', "%{$query}%"))
                    ->orWhereHas('program', fn ($p) => $p->where('name', 'like', "%{$query}%"));
            })
            ->orderByDesc('generated_at')
            ->limit(5)
            ->get()
            ->map(function (PortfolioReport $report) {
                $typeLabel = config('projects.portfolio_report_types.'.$report->report_type, $report->report_type);

                return [
                    'type' => __('Portfolio Report'),
                    'label' => __('Portfolio Reports'),
                    'title' => is_string($typeLabel) ? $typeLabel : (string) $report->report_type,
                    'subtitle' => collect([
                        $report->portfolio?->name ?? $report->program?->name,
                        strtoupper((string) $report->format),
                    ])->filter()->implode(' · ') ?: null,
                    'url' => $this->safeRoute('portfolio-reports.show', $report, 'portfolio-reports.index'),
                ];
            });
    }

    protected function safeRoute(string $name, mixed $parameters = [], ?string $fallback = null): string
    {
        if (Route::has($name)) {
            return route($name, $parameters);
        }

        if ($fallback && Route::has($fallback)) {
            return route($fallback);
        }

        return Route::has('projects.index') ? route('projects.index') : url('/');
    }
}
