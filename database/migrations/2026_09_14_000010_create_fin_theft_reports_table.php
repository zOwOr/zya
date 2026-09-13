<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_theft_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_code')->unique()->index();
            $table->foreignId('device_id')->constrained('fin_devices')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('fin_sales')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('incident_date');
            $table->string('police_report_number')->nullable();
            $table->text('description');
            $table->enum('status', ['reportado', 'en_investigacion', 'recuperado', 'cerrado'])->default('reportado')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_theft_reports');
    }
};
