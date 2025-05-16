<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('repairs', function (Blueprint $table) {
        $table->id();
        $table->string('cliente');
        $table->string('telefono');
        $table->string('marca');
        $table->string('modelo');
        $table->string('imei');
        $table->text('problema_reportado');
        $table->text('diagnostico')->nullable();
        $table->string('foto_recibido_frontal')->nullable();
        $table->string('foto_recibido_trasera')->nullable();
        $table->string('foto_entregado_frontal')->nullable();
        $table->string('foto_entregado_trasera')->nullable();
        $table->decimal('precio', 8, 2)->nullable();
        $table->date('fecha_ingreso');
        $table->date('fecha_entrega');
        $table->enum('estado', ['pendiente', 'en reparación', 'reparado', 'entregado'])->default('pendiente');
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
