<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_devices', function (Blueprint $table) {
            $table->id();
            $table->string('imei')->unique()->index();
            $table->foreignId('brand_id')->nullable()->constrained('fin_brands')->nullOnDelete();
            $table->string('model');
            $table->string('color')->nullable();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->enum('status', ['disponible', 'vendido', 'en_garantia', 'robado'])->default('disponible')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_devices');
    }
};
