<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Task;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LeadPolicy;
use App\Policies\OpportunityPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\ProductPolicy;
use App\Policies\QuotationPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use App\Services\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Organization::class => OrganizationPolicy::class,
        Lead::class => LeadPolicy::class,
        Customer::class => CustomerPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Opportunity::class => OpportunityPolicy::class,
        Payment::class => PaymentPolicy::class,
        Product::class => ProductPolicy::class,
        Quotation::class => QuotationPolicy::class,
        Task::class => TaskPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        require_once app_path('helpers.php');

        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Opportunity::class, OpportunityPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Quotation::class, QuotationPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

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
    }
}
