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
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('movement_type');
            $table->decimal('amount', 10, 2);
            $table->text('justification');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Asegúrate de esta línea
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('movements');
    }
};
