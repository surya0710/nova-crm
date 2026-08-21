<?php

use App\Http\Controllers\Portal\ClientAuthController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortalCommercialController;
use App\Http\Controllers\Portal\PortalDeliverableController;
use App\Http\Controllers\Portal\PortalDiscussionController;
use App\Http\Controllers\Portal\PortalProjectController;
use App\Http\Controllers\Portal\PortalUploadRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('{organization:slug}/portal')
    ->middleware(['portal.organization'])
    ->name('portal.')
    ->group(function () {
        Route::middleware('guest:client')->group(function () {
            Route::get('/login', [ClientAuthController::class, 'createLogin'])->name('login');
            Route::post('/login', [ClientAuthController::class, 'login'])->middleware('throttle:6,1');
        });

        Route::middleware(['auth:client', 'portal.client'])->group(function () {
            Route::post('/logout', [ClientAuthController::class, 'logout'])->name('logout');
            Route::get('/', [PortalDashboardController::class, 'index'])->name('dashboard');
            Route::get('/billing', [PortalCommercialController::class, 'overview'])->name('commercial.overview');
            Route::get('/quotations', [PortalCommercialController::class, 'quotations'])->name('commercial.quotations');
            Route::get('/quotations/{quotation}', [PortalCommercialController::class, 'showQuotation'])->name('commercial.quotations.show');
            Route::post('/quotations/{quotation}/accept', [PortalCommercialController::class, 'acceptQuotation'])->name('commercial.quotations.accept');
            Route::post('/quotations/{quotation}/reject', [PortalCommercialController::class, 'rejectQuotation'])->name('commercial.quotations.reject');
            Route::get('/quotations/{quotation}/pdf', [PortalCommercialController::class, 'quotationPdf'])->name('commercial.quotations.pdf');
            Route::get('/sales-orders', [PortalCommercialController::class, 'salesOrders'])->name('commercial.sales-orders');
            Route::get('/sales-orders/{sales_order}', [PortalCommercialController::class, 'showSalesOrder'])->name('commercial.sales-orders.show');
            Route::get('/sales-orders/{sales_order}/pdf', [PortalCommercialController::class, 'salesOrderPdf'])->name('commercial.sales-orders.pdf');
            Route::get('/invoices', [PortalCommercialController::class, 'invoices'])->name('commercial.invoices');
            Route::get('/invoices/{invoice}', [PortalCommercialController::class, 'showInvoice'])->name('commercial.invoices.show');
            Route::get('/invoices/{invoice}/pdf', [PortalCommercialController::class, 'invoicePdf'])->name('commercial.invoices.pdf');
            Route::post('/invoices/{invoice}/pay', [PortalCommercialController::class, 'payInvoice'])->name('commercial.invoices.pay');
            Route::get('/payments', [PortalCommercialController::class, 'payments'])->name('commercial.payments');
            Route::get('/notes', [PortalCommercialController::class, 'notes'])->name('commercial.notes');
            Route::get('/notes/{adjustment_note}/pdf', [PortalCommercialController::class, 'notePdf'])->name('commercial.notes.pdf');
            Route::get('/projects/{project}', [PortalProjectController::class, 'show'])->name('projects.show');
            Route::get('/deliverables/{deliverable}', [PortalDeliverableController::class, 'show'])->name('deliverables.show');
            Route::post('/approvals/{approval}/approve', [PortalDeliverableController::class, 'approve'])->name('approvals.approve');
            Route::post('/approvals/{approval}/reject', [PortalDeliverableController::class, 'reject'])->name('approvals.reject');
            Route::post('/projects/{project}/discussions', [PortalDiscussionController::class, 'store'])->name('discussions.store');
            Route::post('/upload-requests/{uploadRequest}/fulfill', [PortalUploadRequestController::class, 'fulfill'])->name('upload-requests.fulfill');
        });
    });
