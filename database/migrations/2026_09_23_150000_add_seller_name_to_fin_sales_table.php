<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('fin_sales', 'seller_name')) {
                $table->string('seller_name', 150)->nullable()->after('seller_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fin_sales', function (Blueprint $table) {
            if (Schema::hasColumn('fin_sales', 'seller_name')) {
                $table->dropColumn('seller_name');
            }
        });
    }
};
