<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_flows', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_flows', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('branch_id')->index();
            }
            if (!Schema::hasColumn('cash_flows', 'pos_transaction_id')) {
                $table->unsignedBigInteger('pos_transaction_id')->nullable()->after('user_id')->index();
            }
            if (!Schema::hasColumn('cash_flows', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('pos_transaction_id')->index();
            }
            if (!Schema::hasColumn('cash_flows', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('customer_id')->index();
            }
            if (!Schema::hasColumn('cash_flows', 'cash_amount')) {
                $table->decimal('cash_amount', 12, 2)->default(0)->after('payment_method');
            }
            if (!Schema::hasColumn('cash_flows', 'card_amount')) {
                $table->decimal('card_amount', 12, 2)->default(0)->after('cash_amount');
            }
            if (!Schema::hasColumn('cash_flows', 'transfer_amount')) {
                $table->decimal('transfer_amount', 12, 2)->default(0)->after('card_amount');
            }
            if (!Schema::hasColumn('cash_flows', 'commission_amount')) {
                $table->decimal('commission_amount', 12, 2)->default(0)->after('transfer_amount');
            }
            if (!Schema::hasColumn('cash_flows', 'daily_cut_id')) {
                $table->unsignedBigInteger('daily_cut_id')->nullable()->after('commission_amount')->index();
            }
        });

        // Remove unique(['branch_id', 'date']) constraint from daily_cuts so Pre-Cut (Corte X) and Final Cut (Corte Z) can coexist
        try {
            Schema::table('daily_cuts', function (Blueprint $table) {
                $table->index('branch_id', 'daily_cuts_branch_id_idx');
            });
        } catch (\Throwable $e) {
            // Ignore if index already exists
        }

        try {
            Schema::table('daily_cuts', function (Blueprint $table) {
                $table->dropUnique(['branch_id', 'date']);
            });
        } catch (\Throwable $e) {
            // Ignore if already dropped
        }

        Schema::table('daily_cuts', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_cuts', 'cut_type')) {
                $table->string('cut_type', 30)->default('corte_z')->after('date')->index();
            }
            if (!Schema::hasColumn('daily_cuts', 'cut_mode')) {
                $table->string('cut_mode', 30)->default('manual')->after('cut_type');
            }
            if (!Schema::hasColumn('daily_cuts', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('cut_mode');
            }
            if (!Schema::hasColumn('daily_cuts', 'cash_income')) {
                $table->decimal('cash_income', 12, 2)->default(0)->after('total_income');
            }
            if (!Schema::hasColumn('daily_cuts', 'card_income')) {
                $table->decimal('card_income', 12, 2)->default(0)->after('cash_income');
            }
            if (!Schema::hasColumn('daily_cuts', 'transfer_income')) {
                $table->decimal('transfer_income', 12, 2)->default(0)->after('card_income');
            }
            if (!Schema::hasColumn('daily_cuts', 'withdraw_amount')) {
                $table->decimal('withdraw_amount', 12, 2)->default(0)->after('total_expense');
            }
            if (!Schema::hasColumn('daily_cuts', 'declared_cash')) {
                $table->decimal('declared_cash', 12, 2)->nullable()->after('balance');
            }
            if (!Schema::hasColumn('daily_cuts', 'difference_amount')) {
                $table->decimal('difference_amount', 12, 2)->default(0)->after('declared_cash');
            }
            if (!Schema::hasColumn('daily_cuts', 'customer_breakdown')) {
                $table->json('customer_breakdown')->nullable()->after('difference_amount');
            }
            if (!Schema::hasColumn('daily_cuts', 'notes')) {
                $table->text('notes')->nullable()->after('customer_breakdown');
            }
            if (!Schema::hasColumn('daily_cuts', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_cuts', function (Blueprint $table) {
            $cols = [
                'cut_type', 'cut_mode', 'user_id', 'cash_income', 'card_income',
                'transfer_income', 'withdraw_amount', 'declared_cash',
                'difference_amount', 'customer_breakdown', 'notes', 'closed_at'
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('daily_cuts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('cash_flows', function (Blueprint $table) {
            $cols = [
                'user_id', 'pos_transaction_id', 'customer_id', 'customer_name',
                'cash_amount', 'card_amount', 'transfer_amount', 'commission_amount', 'daily_cut_id'
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('cash_flows', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

