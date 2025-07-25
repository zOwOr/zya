<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
// database/migrations/xxxx_create_payment_histories_table.php
public function up()
{
    Schema::create('payment_histories', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('order_id');
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('branch_id')->nullable();
        $table->decimal('amount', 10, 2);
        $table->enum('method', ['HandCash', 'Cheque', 'Due']);
        $table->timestamps();

        $table->foreign('order_id')->references('id')->on('orders');
        $table->foreign('user_id')->references('id')->on('users');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
    }
};
