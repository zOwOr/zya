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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('alternate_phone', 15)->nullable();
            $table->string('position')->nullable();
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->string('income_receipt_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['alternate_phone', 'position', 'monthly_income', 'income_receipt_path']);
        });
    }
};
