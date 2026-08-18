<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
            $table->index(['organization_id', 'is_active']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_category_id')->nullable()->after('category')->constrained('product_categories')->nullOnDelete();
            $table->decimal('cost_price', 15, 2)->nullable()->after('unit_price');
            $table->decimal('default_discount_percent', 5, 2)->default(0)->after('tax_rate');
            $table->string('hsn_sac', 20)->nullable()->after('default_discount_percent');
            $table->boolean('tax_inclusive')->default(false)->after('hsn_sac');
            $table->decimal('cess_rate', 5, 2)->default(0)->after('tax_inclusive');
            $table->json('custom_fields')->nullable()->after('status');

            $table->index(['organization_id', 'hsn_sac']);
            $table->index(['organization_id', 'product_category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_category_id');
            $table->dropColumn([
                'cost_price',
                'default_discount_percent',
                'hsn_sac',
                'tax_inclusive',
                'cess_rate',
                'custom_fields',
            ]);
        });

        Schema::dropIfExists('product_categories');
    }
};
