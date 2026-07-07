@if (session('status'))
    @php
        $messages = [
            'organization-created' => __('Your organization has been created successfully.'),
            'organization-updated' => __('Organization settings saved.'),
            'organization-mail-test-sent' => __('Test email sent successfully.'),
            'organization-switched' => __('Organization switched successfully.'),
            'lead-created' => __('Lead created successfully.'),
            'lead-updated' => __('Lead updated successfully.'),
            'lead-status-updated' => __('Lead status updated successfully.'),
            'lead-deleted' => __('Lead deleted successfully.'),
            'lead-note-added' => __('Note added successfully.'),
            'lead-follow-up-updated' => __('Follow-up schedule saved.'),
            'lead-converted' => __('Lead converted successfully.'),
            'lead-converted-with-opportunity' => __('Lead converted successfully. An opportunity was also created in the pipeline.'),
            'team-member-added' => __('Team member added successfully.'),
            'team-member-updated' => __('Member role updated successfully.'),
            'team-member-removed' => __('Team member removed successfully.'),
            'customer-created' => __('Customer created successfully.'),
            'customer-updated' => __('Customer updated successfully.'),
            'customer-deleted' => __('Customer deleted successfully.'),
            'customer-note-added' => __('Note added successfully.'),
            'opportunity-created' => __('Deal created successfully.'),
            'opportunity-updated' => __('Deal updated successfully.'),
            'opportunity-stage-updated' => __('Deal stage updated successfully.'),
            'opportunity-won' => __('Deal marked as won.'),
            'opportunity-lost' => __('Deal marked as lost.'),
            'opportunity-deleted' => __('Deal deleted successfully.'),
            'opportunity-note-added' => __('Note added successfully.'),
            'product-created' => __('Product created successfully.'),
            'product-updated' => __('Product updated successfully.'),
            'product-deleted' => __('Product deleted successfully.'),
            'quotation-created' => __('Quotation created successfully.'),
            'quotation-updated' => __('Quotation updated successfully.'),
            'quotation-status-updated' => __('Quotation status updated successfully.'),
            'quotation-deleted' => __('Quotation deleted successfully.'),
            'quotation-email-sent' => __('Quotation emailed successfully.'),
            'invoice-created' => __('Invoice created successfully.'),
            'invoice-created-from-quotation' => __('Invoice generated from quotation successfully.'),
            'invoice-issued' => __('Invoice issued successfully.'),
            'invoice-cancelled' => __('Invoice cancelled successfully.'),
            'invoice-updated' => __('Invoice updated successfully.'),
            'invoice-status-updated' => __('Invoice status updated successfully.'),
            'invoice-payment-recorded' => __('Payment recorded successfully.'),
            'invoice-email-sent' => __('Invoice emailed successfully.'),
            'invoice-deleted' => __('Invoice deleted successfully.'),
            'payment-recorded' => __('Payment recorded successfully.'),
            'payment-deleted' => __('Payment deleted successfully.'),
            'payment-email-sent' => __('Payment receipt emailed successfully.'),
            'customer-email-sent' => __('Email sent to customer successfully.'),
            'attachment-uploaded' => __('File uploaded successfully.'),
            'attachment-deleted' => __('File deleted successfully.'),
            'notifications-read' => __('All notifications marked as read.'),
            'api-token-created' => __('API token created successfully.'),
            'api-token-deleted' => __('API token revoked.'),
            'task-created' => __('Task created successfully.'),
            'task-updated' => __('Task updated successfully.'),
            'task-completed' => __('Task marked as complete.'),
            'task-deleted' => __('Task deleted successfully.'),
        ];
        $message = $messages[session('status')] ?? session('status');
    @endphp
    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ $message }}
    </div>
@endif

@if (session('error'))
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        {{ session('error') }}
    </div>
@endif
