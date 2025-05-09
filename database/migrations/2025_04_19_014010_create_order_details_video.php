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
        Schema::create('order_details_video', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('video');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Asegúrate de esta línea
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_details_video');
    }
};
