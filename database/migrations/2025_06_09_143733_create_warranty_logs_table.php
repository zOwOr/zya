<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repairs_id')->constrained()->onDelete('cascade');
            $table->string('tipo_garantia')->nullable(); // nuevo campo
            $table->string('accion'); // Ejemplo: "Cambio de estado", "Entrega", "Observación"
            $table->text('descripcion')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Quién hizo el movimiento (opcional)
            $table->timestamps(); // created_at será la fecha del movimiento
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_logs');
    }
};
