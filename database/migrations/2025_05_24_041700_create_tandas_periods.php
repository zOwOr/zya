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
        Schema::create('tanda_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tanda_id')->constrained('tandas')->onDelete('cascade')->nullable(); // or ->restrict(), ->setNull(), etc.
            $table->unsignedInteger('period_number')->nullable(); // Week 1, 2, 3...
            $table->date('due_date')->nullable(); // Payment due date
            $table->boolean('is_paid')->default(false);
            $table->decimal('paid_amount', 10, 2)->nullable();
            $table->date('paid_date')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tanda_periods');
    }
};
