<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fin_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('fin_sales', 'sale_type')) {
                $table->string('sale_type', 20)->default('credito')->after('sale_code')->index();
            }
            if (!Schema::hasColumn('fin_sales', 'customer_rfc')) {
                $table->string('customer_rfc', 25)->nullable()->after('customer_address');
            }
            if (!Schema::hasColumn('fin_sales', 'warranty_text')) {
                $table->string('warranty_text', 100)->nullable()->after('term_months');
            }
            if (!Schema::hasColumn('fin_sales', 'payment_method')) {
                $table->string('payment_method', 50)->nullable()->after('warranty_text');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fin_sales', function (Blueprint $table) {
            $table->dropIndex(['sale_type']);
            $table->dropColumn(['sale_type', 'customer_rfc', 'warranty_text', 'payment_method']);
        });
    }
};
