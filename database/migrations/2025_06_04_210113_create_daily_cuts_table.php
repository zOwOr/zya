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

        // Nueva columna de sucursal
        $table->unsignedBigInteger('branch_id');

        // Fecha única por sucursal
        $table->date('date');

        $table->decimal('total_income', 10, 2);
        $table->decimal('total_expense', 10, 2);
        $table->decimal('balance', 10, 2);
        $table->timestamps();

        // Relaciones y restricciones
        $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');

        // Evita cortes duplicados por sucursal y día
        $table->unique(['branch_id', 'date']);
    });
}


    public function down(): void
    {
        Schema::dropIfExists('daily_cuts');
    }
};
