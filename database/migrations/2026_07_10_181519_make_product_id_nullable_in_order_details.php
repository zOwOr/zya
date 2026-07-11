<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Permite que product_id sea null en order_details para productos dinámicos
     * (creados manualmente en el POS) que no tienen registro en la tabla products.
     */
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->string('product_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->string('product_id')->nullable(false)->change();
        });
    }
};
