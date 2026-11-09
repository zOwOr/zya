<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gifts')) {
            Schema::create('gifts', function (Blueprint $table) {
                $table->id();
                $table->string('code', 80)->unique();
                $table->string('name');
                $table->string('brand')->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('supplier_id')->nullable()->index();
                $table->unsignedBigInteger('fin_supplier_id')->nullable()->index();
                $table->string('supplier_name')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->integer('stock_quantity')->default(0);
                $table->integer('min_stock')->default(5);
                $table->decimal('unit_cost', 10, 2)->default(0);
                $table->enum('status', ['activo', 'inactivo'])->default('activo')->index();
                $table->timestamps();

                $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
                $table->foreign('fin_supplier_id')->references('id')->on('fin_suppliers')->nullOnDelete();
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('gift_movements')) {
            Schema::create('gift_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('gift_id')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->enum('movement_type', ['entrada_proveedor', 'entrega_cliente', 'ajuste'])->index();
                $table->integer('quantity');
                $table->integer('previous_stock')->default(0);
                $table->integer('new_stock')->default(0);
                $table->string('supplier_name')->nullable();
                $table->string('customer_name')->nullable()->index();
                $table->string('customer_phone', 50)->nullable();
                $table->string('reference_module', 60)->nullable();
                $table->string('reference_code', 100)->nullable();
                $table->text('notes')->nullable();
                $table->dateTime('movement_date')->index();
                $table->timestamps();

                $table->foreign('gift_id')->references('id')->on('gifts')->cascadeOnDelete();
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_movements');
        Schema::dropIfExists('gifts');
    }
};

