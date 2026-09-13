<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_warranties', function (Blueprint $table) {
            $table->id();
            $table->string('warranty_code')->unique()->index();
            $table->foreignId('device_id')->constrained('fin_devices')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('fin_sales')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('current_stage_id')->constrained('fin_warranty_stages')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('issue_description');
            $table->text('resolution_notes')->nullable();
            $table->enum('status', ['abierta', 'en_proceso', 'resuelta', 'rechazada'])->default('abierta')->index();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_warranties');
    }
};
