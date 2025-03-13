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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('tit_name');
            $table->string('tit_email')->unique();
            $table->string('tit_phone')->unique();
            $table->text('tit_address')->nullable();
            $table->string('tit_photo')->nullable();
            $table->string('tit_photo_ine_f')->nullable();
            $table->string('tit_photo_ine_b')->nullable();
            $table->string('tit_facebook')->nullable();
            $table->string('tit_photo_home')->nullable();
            $table->string('tit_link_location')->nullable();
            $table->string('tit_photo_proof_address')->nullable();
            $table->string('tit_work')->nullable();
            $table->string('tit_city')->nullable();

            $table->string('ref1_name')->nullable();
            $table->string('ref1_phone')->nullable();
            $table->string('ref1_address')->nullable();
            
            $table->string('ref2_name')->nullable();
            $table->string('ref2_phone')->nullable();
            $table->string('ref2_address')->nullable();

            $table->string('ref3_name')->nullable();
            $table->string('ref3_phone')->nullable();
            $table->string('ref3_address')->nullable();

            $table->string('aval_name')->nullable();
            $table->string('aval_phone')->nullable();
            $table->string('aval_address')->nullable();
            $table->string('aval_photo_ine_f')->nullable();
            $table->string('aval_photo_ine_b')->nullable();
            $table->string('aval_photo_home')->nullable();



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
