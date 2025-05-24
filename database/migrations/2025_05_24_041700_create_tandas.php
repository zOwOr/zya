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
        Schema::create('tandas', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->decimal('total_amount')->nullable();
            $table->string('payment_amount')->nullable();
            $table->enum('payment_period', ['semana', 'quincena', 'mes'])->nullable();
            $table->string('slug')->unique()->nullable();



            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tandas');
    }
};
