<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'auto_cut_enabled')) {
                $table->boolean('auto_cut_enabled')->default(false)->after('phone');
            }
            if (!Schema::hasColumn('branches', 'auto_cut_time')) {
                $table->string('auto_cut_time', 5)->default('22:00')->after('auto_cut_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'auto_cut_time')) {
                $table->dropColumn('auto_cut_time');
            }
            if (Schema::hasColumn('branches', 'auto_cut_enabled')) {
                $table->dropColumn('auto_cut_enabled');
            }
        });
    }
};

