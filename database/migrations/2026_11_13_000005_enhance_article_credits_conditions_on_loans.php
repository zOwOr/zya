<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajusta la tabla loans para soportar ventas a crédito sin precio de contado ni monto
     * financiado obligatorios, almacenando el total final de venta y la fecha del primer pago.
     */
    public function up(): void
    {
        if (!Schema::hasTable('loans')) {
            return;
        }

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                'ALTER TABLE `loans`
                MODIFY COLUMN `principal_amount` DECIMAL(12, 2) NULL DEFAULT NULL,
                MODIFY COLUMN `interest_rate` DECIMAL(8, 2) NULL DEFAULT NULL,
                MODIFY COLUMN `interest_amount` DECIMAL(12, 2) NULL DEFAULT NULL'
            );
        }

        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'credit_sale_total')) {
                $table->decimal('credit_sale_total', 12, 2)->nullable()->after('total_amount');
            }
            if (!Schema::hasColumn('loans', 'first_payment_date')) {
                $table->date('first_payment_date')->nullable()->after('start_date');
            }
        });
    }

    /**
     * Revierte las columnas agregadas manteniendo intactos los registros históricos.
     */
    public function down(): void
    {
        if (!Schema::hasTable('loans')) {
            return;
        }

        Schema::table('loans', function (Blueprint $table) {
            foreach (['first_payment_date', 'credit_sale_total'] as $column) {
                if (Schema::hasColumn('loans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::table('loans')->whereNull('principal_amount')->update(['principal_amount' => 0]);
            DB::table('loans')->whereNull('interest_rate')->update(['interest_rate' => 0]);
            DB::table('loans')->whereNull('interest_amount')->update(['interest_amount' => 0]);

            DB::statement(
                'ALTER TABLE `loans`
                MODIFY COLUMN `principal_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                MODIFY COLUMN `interest_rate` DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
                MODIFY COLUMN `interest_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00'
            );
        }
    }
};

