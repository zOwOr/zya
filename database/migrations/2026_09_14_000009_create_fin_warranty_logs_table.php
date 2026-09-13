<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_warranty_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warranty_id')->constrained('fin_warranties')->cascadeOnDelete();
            $table->foreignId('from_stage_id')->nullable()->constrained('fin_warranty_stages')->nullOnDelete();
            $table->foreignId('to_stage_id')->constrained('fin_warranty_stages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_warranty_logs');
    }
};
