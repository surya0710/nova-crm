<?php

return [
    'categories' => [
        'general' => 'General',
        'sales' => 'Sales',
        'quotation' => 'Quotation',
        'sales_order' => 'Sales Order',
        'invoice' => 'Invoice',
        'payment' => 'Payment',
        'support' => 'Support',
        'hr' => 'HR',
    ],

    'modules' => [
        'customers' => 'Customers',
        'contacts' => 'Contacts',
        'opportunities' => 'Opportunities',
        'tickets' => 'Tickets',
        'quotations' => 'Quotations',
        'sales_orders' => 'Sales Orders',
        'invoices' => 'Invoices',
        'payments' => 'Payments',
        'hrms' => 'HR',
    ],

    'category_modules' => [
        'general' => ['customers', 'contacts', 'opportunities', 'tickets', 'quotations', 'sales_orders', 'invoices', 'payments'],
        'sales' => ['customers', 'contacts', 'opportunities', 'quotations', 'sales_orders'],
        'quotation' => ['quotations'],
        'sales_order' => ['sales_orders'],
        'invoice' => ['invoices'],
        'payment' => ['payments'],
        'support' => ['tickets', 'customers', 'contacts'],
        'hr' => ['hrms'],
    ],

    'category_license' => [
        'hr' => 'hrms',
    ],

    'variables' => [
        'customer.name' => 'Customer name',
        'contact.name' => 'Contact name',
        'company.name' => 'Organization name',
        'opportunity.name' => 'Opportunity title',
        'quotation.number' => 'Quotation number',
        'sales_order.number' => 'Sales order number',
        'invoice.number' => 'Invoice number',
        'invoice.total' => 'Invoice total',
        'payment.amount' => 'Payment amount',
        'ticket.number' => 'Ticket number',
        'ticket.subject' => 'Ticket subject',
    ],

    'category_variables' => [
        'general' => ['customer.name', 'contact.name', 'company.name'],
        'sales' => ['customer.name', 'contact.name', 'company.name', 'opportunity.name'],
        'quotation' => ['customer.name', 'contact.name', 'company.name', 'quotation.number'],
        'sales_order' => ['customer.name', 'contact.name', 'company.name', 'sales_order.number'],
        'invoice' => ['customer.name', 'contact.name', 'company.name', 'invoice.number', 'invoice.total'],
        'payment' => ['customer.name', 'contact.name', 'company.name', 'payment.amount', 'invoice.number'],
        'support' => ['customer.name', 'contact.name', 'company.name', 'ticket.number', 'ticket.subject'],
        'hr' => ['company.name'],
    ],

    'statuses' => [
        'queued' => 'Queued',
        'sending' => 'Sending',
        'sent' => 'Sent',
        'delivered' => 'Delivered',
        'failed' => 'Failed',
        'bounced' => 'Bounced',
    ],

    'queue' => [
        'connection' => env('CRM_EMAIL_QUEUE_CONNECTION'),
        'name' => env('CRM_EMAIL_QUEUE', 'mail'),
        'tries' => 3,
        'backoff' => [30, 120],
        'timeout' => 120,
    ],

    'tracking_providers' => ['sendgrid', 'mailgun'],

    'commercial_modules' => [
        'quotations',
        'sales_orders',
        'invoices',
        'payments',
        'credit_notes',
        'debit_notes',
    ],

    'related_types' => [
        'customer' => \App\Models\Customer::class,
        'contact' => \App\Models\Contact::class,
        'lead' => \App\Models\Lead::class,
        'opportunity' => \App\Models\Opportunity::class,
        'ticket' => \App\Models\CustomerTicket::class,
        'quotation' => \App\Models\Quotation::class,
        'sales_order' => \App\Models\SalesOrder::class,
        'invoice' => \App\Models\Invoice::class,
        'payment' => \App\Models\Payment::class,
        'adjustment_note' => \App\Models\AdjustmentNote::class,
    ],
];
