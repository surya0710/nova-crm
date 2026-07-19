<?php

namespace App\Providers;

use App\Events\CustomerCreated;
use App\Events\CustomerUpdated;
use App\Events\InvoiceCreated;
use App\Events\LeadAssigned;
use App\Events\LeadConverted;
use App\Events\LeadCreated;
use App\Events\LeadUpdated;
use App\Events\MarketingLeadImported;
use App\Events\OpportunityCreated;
use App\Events\OpportunityStageChanged;
use App\Events\PaymentReceived;
use App\Listeners\RunTriggeredWorkflows;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SavedFilter;
use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Policies\CustomerPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LeadPolicy;
use App\Policies\MetadataFieldDefinitionPolicy;
use App\Policies\OpportunityPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\QuotationPolicy;
use App\Policies\SavedFilterPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkflowExecutionPolicy;
use App\Policies\WorkflowPolicy;
use App\Services\Assignment\AssignmentStrategyRegistry;
use App\Services\Import\Adapters\CustomerImportAdapter;
use App\Services\Import\Adapters\LeadImportAdapter;
use App\Services\Import\ImportEntityRegistry;
use App\Services\Marketing\Providers\MarketingProviderRegistry;
use App\Services\TenantContext;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Organization::class => OrganizationPolicy::class,
        Lead::class => LeadPolicy::class,
        MetadataFieldDefinition::class => MetadataFieldDefinitionPolicy::class,
        Customer::class => CustomerPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Opportunity::class => OpportunityPolicy::class,
        Payment::class => PaymentPolicy::class,
        Product::class => ProductPolicy::class,
        Quotation::class => QuotationPolicy::class,
        SavedFilter::class => SavedFilterPolicy::class,
        Task::class => TaskPolicy::class,
        User::class => UserPolicy::class,
        Workflow::class => WorkflowPolicy::class,
        WorkflowExecution::class => WorkflowExecutionPolicy::class,
    ];

    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(WorkflowRuntimeContext::class);

        $this->app->singleton(ImportEntityRegistry::class);

        $this->app->singleton(MarketingProviderRegistry::class, function ($app) {
            $registry = new MarketingProviderRegistry;

            foreach (config('marketing.providers.drivers', []) as $class) {
                $registry->register($app->make($class));
            }

            return $registry;
        });

        $this->app->singleton(AssignmentStrategyRegistry::class, function ($app) {
            $registry = new AssignmentStrategyRegistry;

            foreach (config('assignment.strategies', []) as $class) {
                $registry->register($app->make($class));
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        require_once app_path('helpers.php');

        Event::listen([
            LeadCreated::class,
            LeadUpdated::class,
            LeadAssigned::class,
            LeadConverted::class,
            CustomerCreated::class,
            CustomerUpdated::class,
            OpportunityCreated::class,
            OpportunityStageChanged::class,
            InvoiceCreated::class,
            PaymentReceived::class,
            MarketingLeadImported::class,
        ], RunTriggeredWorkflows::class);

        $this->app->make(ImportEntityRegistry::class)
            ->register($this->app->make(LeadImportAdapter::class));
        $this->app->make(ImportEntityRegistry::class)
            ->register($this->app->make(CustomerImportAdapter::class));

        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(MetadataFieldDefinition::class, MetadataFieldDefinitionPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Opportunity::class, OpportunityPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Quotation::class, QuotationPolicy::class);
        Gate::policy(SavedFilter::class, SavedFilterPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(WorkflowExecution::class, WorkflowExecutionPolicy::class);

        Gate::before(function ($user, string $ability) {
            if (! $user instanceof User) {
                return null;
            }

            if ($user->is_super_admin) {
                return true;
            }

            return null;
        });

        Gate::define('permission', function (User $user, string $permission) {
            return $user->hasPermission($permission);
        });

        RateLimiter::for('api-lead-intake', function (Request $request) {
            $tokenId = $request->user()?->currentAccessToken()?->id;

            return Limit::perMinute(60)->by($tokenId ?? $request->ip());
        });

        RateLimiter::for('marketing-tracking', function (Request $request) {
            return Limit::perMinute((int) config('marketing.tracking.rate_limit_per_minute'))
                ->by($request->ip());
        });

        RateLimiter::for('marketing-webhooks', function (Request $request) {
            return Limit::perMinute((int) config('marketing.providers.webhook_rate_limit_per_minute', 120))
                ->by($request->ip());
        });
    }
}
