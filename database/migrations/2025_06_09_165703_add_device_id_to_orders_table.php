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
        if (!Schema::hasColumn('orders', 'device_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('device_id')->nullable()->after('due');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'device_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('device_id');
            });
        }
    }
};
