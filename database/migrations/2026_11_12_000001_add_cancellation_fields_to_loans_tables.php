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
            if (! Schema::hasColumn('loans', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('closing_final_paid');
            }
            if (! Schema::hasColumn('loans', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('loans', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            }
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE loan_installments MODIFY COLUMN status ENUM('pendiente','parcial','pagado','vencido','cerrado_manual','cancelado') NOT NULL DEFAULT 'pendiente'"
            );
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::table('loan_installments')
                ->where('status', 'cancelado')
                ->update(['status' => 'cerrado_manual']);

            DB::statement(
                "ALTER TABLE loan_installments MODIFY COLUMN status ENUM('pendiente','parcial','pagado','vencido','cerrado_manual') NOT NULL DEFAULT 'pendiente'"
            );
        }

        Schema::table('loans', function (Blueprint $table) {
            if (Schema::hasColumn('loans', 'cancelled_by')) {
                $table->dropForeign(['cancelled_by']);
                $table->dropColumn('cancelled_by');
            }
            if (Schema::hasColumn('loans', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }
            if (Schema::hasColumn('loans', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
        });
    }
};

