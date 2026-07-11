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
        Schema::table('order_details', function (Blueprint $table) {
            $table->boolean('is_dynamic')->default(false)->after('total');
            $table->string('dynamic_product_name')->nullable()->after('is_dynamic');
            $table->string('dynamic_brand')->nullable()->after('dynamic_product_name');
            $table->string('dynamic_model')->nullable()->after('dynamic_brand');
            $table->string('dynamic_imei')->nullable()->after('dynamic_model');
            $table->string('dynamic_category_status')->nullable()->after('dynamic_imei');
            $table->string('dynamic_warranty_time')->nullable()->after('dynamic_category_status');
            $table->text('dynamic_observations')->nullable()->after('dynamic_warranty_time');
            $table->string('dynamic_product_code')->nullable()->after('dynamic_observations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn([
                'is_dynamic',
                'dynamic_product_name',
                'dynamic_brand',
                'dynamic_model',
                'dynamic_imei',
                'dynamic_category_status',
                'dynamic_warranty_time',
                'dynamic_observations',
                'dynamic_product_code',
            ]);
        });
    }
};
