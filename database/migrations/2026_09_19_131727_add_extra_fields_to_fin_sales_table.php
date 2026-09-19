<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_sales', function (Blueprint $table) {
            // Datos del equipo / contrato financiera
            $table->string('tag_contrato')->nullable()->after('credit_amount');
            $table->decimal('enganche_descuento', 12, 2)->nullable()->after('tag_contrato');
            $table->decimal('abono_semanal', 12, 2)->nullable()->after('enganche_descuento');
            $table->integer('term_weeks')->nullable()->after('abono_semanal');

            // Datos extra del cliente
            $table->string('customer_chip')->nullable()->after('customer_address');
            $table->string('customer_facebook')->nullable()->after('customer_chip');

            // Referencias personales
            $table->string('ref1_name')->nullable()->after('customer_facebook');
            $table->string('ref1_phone')->nullable()->after('ref1_name');
            $table->string('ref2_name')->nullable()->after('ref1_phone');
            $table->string('ref2_phone')->nullable()->after('ref2_name');
            $table->string('ref3_name')->nullable()->after('ref2_phone');
            $table->string('ref3_phone')->nullable()->after('ref3_name');
        });
    }

    public function down(): void
    {
        Schema::table('fin_sales', function (Blueprint $table) {
            $table->dropColumn([
                'tag_contrato',
                'enganche_descuento',
                'abono_semanal',
                'term_weeks',
                'customer_chip',
                'customer_facebook',
                'ref1_name', 'ref1_phone',
                'ref2_name', 'ref2_phone',
                'ref3_name', 'ref3_phone',
            ]);
        });
    }
};
