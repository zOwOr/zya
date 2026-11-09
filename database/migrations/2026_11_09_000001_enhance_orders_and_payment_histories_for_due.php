<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('customer_id')->index();
            }
            if (!Schema::hasColumn('orders', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('branch_id')->index();
            }
        });

        Schema::table('payment_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_histories', 'note')) {
                $table->text('note')->nullable()->after('method');
            }
            if (!Schema::hasColumn('payment_histories', 'cash_amount')) {
                $table->decimal('cash_amount', 12, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('payment_histories', 'card_amount')) {
                $table->decimal('card_amount', 12, 2)->default(0)->after('cash_amount');
            }
            if (!Schema::hasColumn('payment_histories', 'transfer_amount')) {
                $table->decimal('transfer_amount', 12, 2)->default(0)->after('card_amount');
            }
        });

        // Modify method in payment_histories to string(50) so it accepts efectivo, tarjeta, transferencia, mixto
        try {
            DB::statement("ALTER TABLE payment_histories MODIFY COLUMN method VARCHAR(50) NOT NULL DEFAULT 'efectivo'");
        } catch (\Throwable $e) {
            // Ignore if DB driver doesn't support raw alter or already modified
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'branch_id')) {
                $table->dropColumn('branch_id');
            }
            if (Schema::hasColumn('orders', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });

        Schema::table('payment_histories', function (Blueprint $table) {
            if (Schema::hasColumn('payment_histories', 'note')) {
                $table->dropColumn('note');
            }
            if (Schema::hasColumn('payment_histories', 'cash_amount')) {
                $table->dropColumn('cash_amount');
            }
            if (Schema::hasColumn('payment_histories', 'card_amount')) {
                $table->dropColumn('card_amount');
            }
            if (Schema::hasColumn('payment_histories', 'transfer_amount')) {
                $table->dropColumn('transfer_amount');
            }
        });
    }
};

