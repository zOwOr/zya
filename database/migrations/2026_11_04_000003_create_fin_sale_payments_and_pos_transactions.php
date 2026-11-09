<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pos_transactions')) {
            Schema::create('pos_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_code')->unique();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('customer_name')->nullable()->index();
                $table->string('customer_phone', 50)->nullable();

                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('commission_total', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('change_amount', 12, 2)->default(0);

                $table->string('payment_method', 50)->default('efectivo')->index();
                $table->decimal('cash_amount', 12, 2)->default(0);
                $table->decimal('card_amount', 12, 2)->default(0);
                $table->decimal('transfer_amount', 12, 2)->default(0);
                $table->json('payment_references')->nullable();

                $table->json('items_payload')->nullable();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->boolean('drawer_opened')->default(true);
                $table->enum('status', ['completado', 'cancelado'])->default('completado')->index();
                $table->text('notes')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->string('cancellation_reason')->nullable();
                $table->timestamps();

                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
                $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('fin_sale_payments')) {
            Schema::create('fin_sale_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fin_sale_id')->index();
                $table->unsignedBigInteger('device_id')->nullable()->index();
                $table->unsignedBigInteger('financiera_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('pos_transaction_id')->nullable()->index();

                $table->string('receipt_code')->unique();
                $table->string('tag_contrato', 120)->nullable()->index();
                $table->string('imei', 100)->nullable()->index();
                $table->string('customer_name')->nullable();

                $table->integer('installment_number')->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->decimal('commission_amount', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);

                $table->string('payment_method', 50)->default('efectivo')->index();
                $table->json('payment_breakdown')->nullable();
                $table->dateTime('payment_date')->index();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('fin_sale_id')->references('id')->on('fin_sales')->cascadeOnDelete();
                $table->foreign('device_id')->references('id')->on('fin_devices')->nullOnDelete();
                $table->foreign('financiera_id')->references('id')->on('fin_financieras')->nullOnDelete();
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('pos_transaction_id')->references('id')->on('pos_transactions')->nullOnDelete();
            });
        }

        Schema::table('fin_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('fin_sales', 'total_abonos_paid')) {
                $table->decimal('total_abonos_paid', 12, 2)->default(0)->after('abono_semanal');
            }
            if (!Schema::hasColumn('fin_sales', 'abonos_count')) {
                $table->integer('abonos_count')->default(0)->after('total_abonos_paid');
            }
            if (!Schema::hasColumn('fin_sales', 'last_abono_at')) {
                $table->timestamp('last_abono_at')->nullable()->after('abonos_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fin_sales', function (Blueprint $table) {
            foreach (['total_abonos_paid', 'abonos_count', 'last_abono_at'] as $col) {
                if (Schema::hasColumn('fin_sales', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('fin_sale_payments');
        Schema::dropIfExists('pos_transactions');
    }
};

