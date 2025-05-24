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
        Schema::create('tanda_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tanda_id')->constrained('tandas')->onDelete('cascade')->nullable(); // or ->restrict(), ->setNull(), etc.
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade')->nullable(); // or ->restrict(), ->setNull(), etc.
            $table->integer('position')->nullable(); 
            $table->json('payments')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tanda_clients');
    }
};
