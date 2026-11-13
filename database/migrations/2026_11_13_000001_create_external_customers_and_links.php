<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('external_customers')) {
            Schema::create('external_customers', function (Blueprint $table) {
                $table->id();
                $table->string('tit_name', 100);
                $table->string('tit_email', 100);
                $table->string('tit_phone', 15)->index();
                $table->string('tit_facebook');
                $table->string('tit_status', 30)->default('aprobado');
                $table->string('tit_city');
                $table->text('tit_address');

                // Campos opcionales compatibles con catálogo estándar de clientes
                $table->string('alternate_phone', 15)->nullable();
                $table->string('tit_work')->nullable();
                $table->string('position')->nullable();
                $table->decimal('monthly_income', 10, 2)->nullable();
                $table->string('tit_link_location', 999)->nullable();
                $table->text('notes')->nullable();

                // Trazabilidad de alta, sucursal y promoción a cliente interno
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->string('status', 20)->default('activo')->index();
                $table->unsignedBigInteger('converted_customer_id')->nullable()->index();
                $table->timestamp('converted_at')->nullable();
                $table->unsignedBigInteger('converted_by')->nullable()->index();

                $table->timestamps();
                $table->softDeletes();

                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('converted_customer_id')->references('id')->on('customers')->nullOnDelete();
                $table->foreign('converted_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('pos_transactions') && !Schema::hasColumn('pos_transactions', 'external_customer_id')) {
            Schema::table('pos_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('external_customer_id')->nullable()->after('customer_id')->index();
                $table->foreign('external_customer_id')->references('id')->on('external_customers')->nullOnDelete();
            });
        }

        if (Schema::hasTable('cash_flows') && !Schema::hasColumn('cash_flows', 'external_customer_id')) {
            Schema::table('cash_flows', function (Blueprint $table) {
                $table->unsignedBigInteger('external_customer_id')->nullable()->after('customer_id')->index();
                $table->foreign('external_customer_id')->references('id')->on('external_customers')->nullOnDelete();
            });
        }

        if (Schema::hasTable('fin_sale_payments')) {
            if (DB::getDriverName() === 'mysql') {
                try {
                    DB::statement('ALTER TABLE `fin_sale_payments` MODIFY `fin_sale_id` BIGINT UNSIGNED NULL');
                } catch (\Throwable $e) {
                    // Continuar si el motor o esquema ya permite nulos
                }
            }

            Schema::table('fin_sale_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('fin_sale_payments', 'external_customer_id')) {
                    $table->unsignedBigInteger('external_customer_id')->nullable()->after('fin_sale_id')->index();
                    $table->foreign('external_customer_id')->references('id')->on('external_customers')->nullOnDelete();
                }
                if (!Schema::hasColumn('fin_sale_payments', 'is_external_company')) {
                    $table->boolean('is_external_company')->default(false)->after('customer_name')->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fin_sale_payments')) {
            Schema::table('fin_sale_payments', function (Blueprint $table) {
                if (Schema::hasColumn('fin_sale_payments', 'external_customer_id')) {
                    $table->dropForeign(['external_customer_id']);
                    $table->dropColumn('external_customer_id');
                }
                if (Schema::hasColumn('fin_sale_payments', 'is_external_company')) {
                    $table->dropColumn('is_external_company');
                }
            });
        }

        if (Schema::hasTable('cash_flows') && Schema::hasColumn('cash_flows', 'external_customer_id')) {
            Schema::table('cash_flows', function (Blueprint $table) {
                $table->dropForeign(['external_customer_id']);
                $table->dropColumn('external_customer_id');
            });
        }

        if (Schema::hasTable('pos_transactions') && Schema::hasColumn('pos_transactions', 'external_customer_id')) {
            Schema::table('pos_transactions', function (Blueprint $table) {
                $table->dropForeign(['external_customer_id']);
                $table->dropColumn('external_customer_id');
            });
        }

        Schema::dropIfExists('external_customers');
    }
};

