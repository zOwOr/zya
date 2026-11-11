<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_cuts', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_cuts', 'denomination_breakdown')) {
                $table->json('denomination_breakdown')->nullable()->after('difference_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_cuts', function (Blueprint $table) {
            if (Schema::hasColumn('daily_cuts', 'denomination_breakdown')) {
                $table->dropColumn('denomination_breakdown');
            }
        });
    }
};

