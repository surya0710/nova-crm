<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Collection;

class SearchService
{
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

        return $results->take($limit)->values();
    }

    protected function searchLeads(string $query): Collection
    {
        return Lead::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('company', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
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
            ->where('title', 'like', "%{$query}%")
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
                    ->orWhere('sku', 'like', "%{$query}%");
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

    protected function searchQuotations(string $query): Collection
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
            ]);
    }

    protected function searchInvoices(string $query): Collection
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
            ]);
    }

    protected function searchPayments(string $query): Collection
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
            ]);
    }
}
