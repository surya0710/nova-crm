<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('gst_state_code', 2)->nullable()->after('tax_number');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('gstin', 15)->nullable()->after('tax_number');
            $table->string('pan', 10)->nullable()->after('gstin');
            $table->string('gst_registration_type', 30)->nullable()->after('pan');
            $table->string('tax_registration_status', 30)->nullable()->after('gst_registration_type');
            $table->string('billing_state_code', 2)->nullable()->after('tax_registration_status');
            $table->string('place_of_supply', 2)->nullable()->after('billing_state_code');
            $table->string('tax_exemption_status', 30)->nullable()->after('place_of_supply');
            $table->string('tax_exemption_reason')->nullable()->after('tax_exemption_status');
            $table->string('default_tax_preference', 30)->nullable()->after('tax_exemption_reason');
            $table->boolean('shipping_same_as_billing')->default(true)->after('default_tax_preference');
            $table->string('shipping_address_line_1')->nullable()->after('shipping_same_as_billing');
            $table->string('shipping_address_line_2')->nullable()->after('shipping_address_line_1');
            $table->string('shipping_city')->nullable()->after('shipping_address_line_2');
            $table->string('shipping_state')->nullable()->after('shipping_city');
            $table->string('shipping_postal_code', 20)->nullable()->after('shipping_state');
            $table->string('shipping_country')->nullable()->after('shipping_postal_code');

            $table->index(['organization_id', 'gstin']);
            $table->index(['organization_id', 'billing_state_code']);
        });

        $this->addDocumentTaxColumns('quotations');
        $this->addDocumentTaxColumns('invoices');
        $this->addLineTaxColumns('quotation_items');
        $this->addLineTaxColumns('invoice_items');
    }

    public function down(): void
    {
        $this->dropLineTaxColumns('invoice_items');
        $this->dropLineTaxColumns('quotation_items');
        $this->dropDocumentTaxColumns('invoices');
        $this->dropDocumentTaxColumns('quotations');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'gstin']);
            $table->dropIndex(['organization_id', 'billing_state_code']);
            $table->dropColumn([
                'gstin',
                'pan',
                'gst_registration_type',
                'tax_registration_status',
                'billing_state_code',
                'place_of_supply',
                'tax_exemption_status',
                'tax_exemption_reason',
                'default_tax_preference',
                'shipping_same_as_billing',
                'shipping_address_line_1',
                'shipping_address_line_2',
                'shipping_city',
                'shipping_state',
                'shipping_postal_code',
                'shipping_country',
            ]);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('gst_state_code');
        });
    }

    protected function addDocumentTaxColumns(string $table): void
    {
        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->decimal('taxable_amount', 15, 2)->default(0)->after('discount_amount');
            $blueprint->decimal('cgst_amount', 15, 2)->default(0)->after('tax_total');
            $blueprint->decimal('sgst_amount', 15, 2)->default(0)->after('cgst_amount');
            $blueprint->decimal('igst_amount', 15, 2)->default(0)->after('sgst_amount');
            $blueprint->decimal('utgst_amount', 15, 2)->default(0)->after('igst_amount');
            $blueprint->decimal('cess_amount', 15, 2)->default(0)->after('utgst_amount');
            $blueprint->decimal('other_tax_amount', 15, 2)->default(0)->after('cess_amount');
            $blueprint->decimal('shipping_amount', 15, 2)->default(0)->after('other_tax_amount');
            $blueprint->string('pricing_mode', 20)->default('exclusive')->after('shipping_amount');
            $blueprint->string('tax_treatment', 20)->default('standard')->after('pricing_mode');
            $blueprint->string('place_of_supply', 2)->nullable()->after('tax_treatment');
            $blueprint->text('terms')->nullable()->after('notes');
        });
    }

    protected function dropDocumentTaxColumns(string $table): void
    {
        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->dropColumn([
                'taxable_amount',
                'cgst_amount',
                'sgst_amount',
                'igst_amount',
                'utgst_amount',
                'cess_amount',
                'other_tax_amount',
                'shipping_amount',
                'pricing_mode',
                'tax_treatment',
                'place_of_supply',
                'terms',
            ]);
        });
    }

    protected function addLineTaxColumns(string $table): void
    {
        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->string('sku', 50)->nullable()->after('product_id');
            $blueprint->string('unit', 30)->nullable()->after('sku');
            $blueprint->string('hsn_sac', 20)->nullable()->after('unit');
            $blueprint->decimal('line_subtotal', 15, 2)->default(0)->after('unit_price');
            $blueprint->decimal('discount_amount', 15, 2)->default(0)->after('discount_percent');
            $blueprint->decimal('taxable_amount', 15, 2)->default(0)->after('discount_amount');
            $blueprint->decimal('tax_amount', 15, 2)->default(0)->after('tax_rate');
            $blueprint->decimal('cgst_rate', 5, 2)->default(0)->after('tax_amount');
            $blueprint->decimal('cgst_amount', 15, 2)->default(0)->after('cgst_rate');
            $blueprint->decimal('sgst_rate', 5, 2)->default(0)->after('cgst_amount');
            $blueprint->decimal('sgst_amount', 15, 2)->default(0)->after('sgst_rate');
            $blueprint->decimal('igst_rate', 5, 2)->default(0)->after('sgst_amount');
            $blueprint->decimal('igst_amount', 15, 2)->default(0)->after('igst_rate');
            $blueprint->decimal('utgst_rate', 5, 2)->default(0)->after('igst_amount');
            $blueprint->decimal('utgst_amount', 15, 2)->default(0)->after('utgst_rate');
            $blueprint->decimal('cess_rate', 5, 2)->default(0)->after('utgst_amount');
            $blueprint->decimal('cess_amount', 15, 2)->default(0)->after('cess_rate');
            $blueprint->boolean('tax_inclusive')->default(false)->after('cess_amount');
        });
    }

    protected function dropLineTaxColumns(string $table): void
    {
        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->dropColumn([
                'sku',
                'unit',
                'hsn_sac',
                'line_subtotal',
                'discount_amount',
                'taxable_amount',
                'tax_amount',
                'cgst_rate',
                'cgst_amount',
                'sgst_rate',
                'sgst_amount',
                'igst_rate',
                'igst_amount',
                'utgst_rate',
                'utgst_amount',
                'cess_rate',
                'cess_amount',
                'tax_inclusive',
            ]);
        });
    }
};
