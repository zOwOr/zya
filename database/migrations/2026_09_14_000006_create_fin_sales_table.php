<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_code')->unique()->index();
            $table->foreignId('device_id')->constrained('fin_devices')->cascadeOnDelete();
            $table->foreignId('financiera_id')->nullable()->constrained('fin_financieras')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Unconditioned customer fields (free fields)
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_ine')->nullable();
            $table->text('customer_address')->nullable();
            
            // Financials
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('down_payment', 12, 2)->default(0);
            $table->decimal('credit_amount', 12, 2)->default(0);
            $table->integer('term_months')->nullable();
            $table->dateTime('sale_date');
            
            // Status & Cancellation
            $table->enum('status', ['activa', 'cancelada'])->default('activa')->index();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_sales');
    }
};
