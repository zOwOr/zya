<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'operation_type')) {
                $table->enum('operation_type', ['prestamo', 'credito_articulo'])
                    ->default('prestamo')
                    ->after('loan_code');
                $table->index('operation_type', 'loans_operation_type_idx');
            }
            if (!Schema::hasColumn('loans', 'delivery_method')) {
                $table->enum('delivery_method', ['efectivo', 'transferencia', 'mercancia'])
                    ->nullable()
                    ->after('operation_type');
            }
            if (!Schema::hasColumn('loans', 'article_price')) {
                $table->decimal('article_price', 12, 2)->nullable()->after('loan_type_id');
            }
            if (!Schema::hasColumn('loans', 'down_payment')) {
                $table->decimal('down_payment', 12, 2)->default(0)->after('article_price');
            }
            if (!Schema::hasColumn('loans', 'down_payment_method')) {
                $table->string('down_payment_method', 30)->nullable()->after('down_payment');
            }
            if (!Schema::hasColumn('loans', 'article_status')) {
                $table->enum('article_status', ['nuevo', 'seminuevo', 'servicio'])->nullable()->after('down_payment_method');
            }
            if (!Schema::hasColumn('loans', 'article_name')) {
                $table->string('article_name', 200)->nullable()->after('article_status');
            }
            if (!Schema::hasColumn('loans', 'article_brand')) {
                $table->string('article_brand', 120)->nullable()->after('article_name');
            }
            if (!Schema::hasColumn('loans', 'article_model')) {
                $table->string('article_model', 120)->nullable()->after('article_brand');
            }
            if (!Schema::hasColumn('loans', 'article_color')) {
                $table->string('article_color', 80)->nullable()->after('article_model');
            }
            if (!Schema::hasColumn('loans', 'article_capacity')) {
                $table->string('article_capacity', 80)->nullable()->after('article_color');
            }
            if (!Schema::hasColumn('loans', 'article_imei')) {
                $table->string('article_imei', 80)->nullable()->after('article_capacity');
            }
            if (!Schema::hasColumn('loans', 'article_serial')) {
                $table->string('article_serial', 120)->nullable()->after('article_imei');
            }
            if (!Schema::hasColumn('loans', 'article_warranty')) {
                $table->string('article_warranty', 150)->nullable()->after('article_serial');
            }
            if (!Schema::hasColumn('loans', 'article_observations')) {
                $table->text('article_observations')->nullable()->after('article_warranty');
            }
        });

        DB::statement("ALTER TABLE loan_payments MODIFY COLUMN payment_type ENUM('comision_apertura', 'abono', 'cierre_manual', 'enganche') NOT NULL DEFAULT 'abono'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE loan_payments MODIFY COLUMN payment_type ENUM('comision_apertura', 'abono', 'cierre_manual') NOT NULL DEFAULT 'abono'");

        Schema::table('loans', function (Blueprint $table) {
            foreach ([
                'article_observations', 'article_warranty', 'article_serial',
                'article_imei', 'article_capacity', 'article_color',
                'article_model', 'article_brand', 'article_name', 'article_status',
                'down_payment_method', 'down_payment', 'article_price',
                'delivery_method', 'operation_type',
            ] as $col) {
                if (Schema::hasColumn('loans', $col)) {
                    if ($col === 'operation_type') {
                        $table->dropIndex('loans_operation_type_idx');
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};

