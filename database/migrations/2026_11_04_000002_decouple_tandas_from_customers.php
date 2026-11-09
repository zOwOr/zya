<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tandas', function (Blueprint $table) {
            if (!Schema::hasColumn('tandas', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('id')->index();
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('tandas', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('branch_id');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('tandas', 'start_date')) {
                $table->date('start_date')->nullable()->after('payment_period');
            }
            if (!Schema::hasColumn('tandas', 'duration')) {
                $table->integer('duration')->default(10)->after('start_date');
            }
            if (!Schema::hasColumn('tandas', 'status')) {
                $table->string('status', 30)->default('activa')->after('duration');
            }
        });

        Schema::table('tanda_clients', function (Blueprint $table) {
            if (!Schema::hasColumn('tanda_clients', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('tanda_clients', 'customer_phone')) {
                $table->string('customer_phone', 50)->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('tanda_clients', 'customer_address')) {
                $table->string('customer_address')->nullable()->after('customer_phone');
            }
            if (!Schema::hasColumn('tanda_clients', 'customer_ine')) {
                $table->string('customer_ine', 100)->nullable()->after('customer_address');
            }
            if (!Schema::hasColumn('tanda_clients', 'customer_notes')) {
                $table->text('customer_notes')->nullable()->after('customer_ine');
            }
            if (!Schema::hasColumn('tanda_clients', 'total_paid')) {
                $table->decimal('total_paid', 12, 2)->default(0)->after('payments');
            }
            if (!Schema::hasColumn('tanda_clients', 'is_delivered')) {
                $table->boolean('is_delivered')->default(false)->after('total_paid');
            }
        });

        // Make customer_id nullable in tanda_clients so tandas are decoupled from customers module
        try {
            DB::statement('ALTER TABLE `tanda_clients` MODIFY `customer_id` BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // Ignore if already nullable or SQLite in tests
        }

        // Backfill customer_name and customer_phone from existing linked customers
        try {
            $existingPivot = DB::table('tanda_clients')
                ->whereNotNull('customer_id')
                ->whereNull('customer_name')
                ->get();

            foreach ($existingPivot as $row) {
                $customer = DB::table('customers')->where('id', $row->customer_id)->first();
                if ($customer) {
                    DB::table('tanda_clients')->where('id', $row->id)->update([
                        'customer_name' => $customer->tit_name ?? 'Participante #' . $row->id,
                        'customer_phone' => $customer->tit_phone ?? null,
                        'customer_address' => $customer->tit_address ?? null,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }

        if (!Schema::hasTable('tanda_payments')) {
            Schema::create('tanda_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tanda_id')->index();
                $table->unsignedBigInteger('tanda_client_id')->index();
                $table->unsignedBigInteger('tanda_period_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('pos_transaction_id')->nullable()->index();

                $table->string('receipt_code')->unique();
                $table->integer('period_number')->default(1);
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('payment_method', 50)->default('efectivo');
                $table->json('payment_breakdown')->nullable();
                $table->dateTime('paid_at');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('tanda_id')->references('id')->on('tandas')->cascadeOnDelete();
                $table->foreign('tanda_client_id')->references('id')->on('tanda_clients')->cascadeOnDelete();
                $table->foreign('tanda_period_id')->references('id')->on('tanda_periods')->nullOnDelete();
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tanda_payments');

        Schema::table('tanda_clients', function (Blueprint $table) {
            $cols = ['customer_name', 'customer_phone', 'customer_address', 'customer_ine', 'customer_notes', 'total_paid', 'is_delivered'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('tanda_clients', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('tandas', function (Blueprint $table) {
            if (Schema::hasColumn('tandas', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
            if (Schema::hasColumn('tandas', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            foreach (['start_date', 'duration', 'status'] as $col) {
                if (Schema::hasColumn('tandas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

