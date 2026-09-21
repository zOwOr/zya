<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fin_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('fin_devices', 'storage')) {
                $table->string('storage', 50)->nullable()->after('color');
            }
            if (!Schema::hasColumn('fin_devices', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('branch_id')->constrained('fin_suppliers')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fin_devices', function (Blueprint $table) {
            if (Schema::hasColumn('fin_devices', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            }
            if (Schema::hasColumn('fin_devices', 'storage')) {
                $table->dropColumn('storage');
            }
        });
    }
};
