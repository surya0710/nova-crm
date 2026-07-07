<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'email']);
            $table->index(['organization_id', 'phone']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'email']);
            $table->index(['organization_id', 'phone']);
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'assigned_to']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->index(['organization_id', 'issue_date']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['organization_id', 'issue_date']);
            $table->index(['organization_id', 'created_by']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['organization_id', 'customer_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['organization_id', 'created_at']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'created_at']);
            $table->dropIndex(['organization_id', 'status', 'due_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'created_at']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'customer_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'issue_date']);
            $table->dropIndex(['organization_id', 'created_by']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'issue_date']);
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'created_at']);
            $table->dropIndex(['organization_id', 'assigned_to']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'created_at']);
            $table->dropIndex(['organization_id', 'email']);
            $table->dropIndex(['organization_id', 'phone']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'created_at']);
            $table->dropIndex(['organization_id', 'email']);
            $table->dropIndex(['organization_id', 'phone']);
        });
    }
};
