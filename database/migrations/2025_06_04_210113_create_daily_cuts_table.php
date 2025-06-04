<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_cuts', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique(); // un corte por día
            $table->decimal('total_income', 10, 2);
            $table->decimal('total_expense', 10, 2);
            $table->decimal('balance', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_cuts');
    }
};
