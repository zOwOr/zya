<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('loan_types')) {
            Schema::create('loan_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->decimal('default_interest_rate', 8, 2)->default(0);
                $table->decimal('default_opening_fee', 10, 2)->default(0);
                $table->boolean('requires_collateral')->default(false);
                $table->enum('default_frequency', ['semanal', 'quincenal', 'mensual'])->default('semanal');
                $table->integer('default_periods')->default(4);
                $table->json('required_fields')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed default loan types so the user immediately has distinct types of loans with custom required info
            $now = now();
            DB::table('loan_types')->insert([
                [
                    'name' => 'Préstamo Personal (Tasa Fija)',
                    'slug' => 'personal',
                    'description' => 'Préstamo en efectivo con pagos periódicos y aval/referencia.',
                    'default_interest_rate' => 20.00,
                    'default_opening_fee' => 150.00,
                    'requires_collateral' => false,
                    'default_frequency' => 'semanal',
                    'default_periods' => 8,
                    'required_fields' => json_encode([
                        ['key' => 'aval_nombre', 'label' => 'Nombre del Aval / Referencia', 'type' => 'text', 'required' => false],
                        ['key' => 'aval_telefono', 'label' => 'Teléfono del Aval', 'type' => 'text', 'required' => false],
                        ['key' => 'destino_prestamo', 'label' => 'Motivo / Destino del Préstamo', 'type' => 'text', 'required' => false],
                    ]),
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Préstamo con Garantía Prendaria (Equipo / Artículo)',
                    'slug' => 'prendario',
                    'description' => 'Préstamo respaldado por equipo celular, electrónico o artículo en garantía.',
                    'default_interest_rate' => 15.00,
                    'default_opening_fee' => 100.00,
                    'requires_collateral' => true,
                    'default_frequency' => 'semanal',
                    'default_periods' => 4,
                    'required_fields' => json_encode([
                        ['key' => 'prenda_articulo', 'label' => 'Artículo / Equipo en Garantía', 'type' => 'text', 'required' => true],
                        ['key' => 'prenda_marca_modelo', 'label' => 'Marca y Modelo', 'type' => 'text', 'required' => false],
                        ['key' => 'prenda_imei_serie', 'label' => 'IMEI / Número de Serie', 'type' => 'text', 'required' => false],
                        ['key' => 'prenda_estado', 'label' => 'Condición / Accesorios Entregados', 'type' => 'text', 'required' => false],
                        ['key' => 'prenda_valor_estimado', 'label' => 'Valor Comercial Estimado ($)', 'type' => 'number', 'required' => false],
                    ]),
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Préstamo Express / Quincenal',
                    'slug' => 'express',
                    'description' => 'Préstamo de corto plazo para clientes frecuentes con comprobante de ingresos.',
                    'default_interest_rate' => 10.00,
                    'default_opening_fee' => 100.00,
                    'requires_collateral' => false,
                    'default_frequency' => 'quincenal',
                    'default_periods' => 4,
                    'required_fields' => json_encode([
                        ['key' => 'empresa_trabajo', 'label' => 'Empresa / Lugar de Trabajo', 'type' => 'text', 'required' => false],
                        ['key' => 'ingreso_declarado', 'label' => 'Ingreso Quincenal Declarado ($)', 'type' => 'number', 'required' => false],
                        ['key' => 'dia_pago_preferido', 'label' => 'Día de Pago Acordado', 'type' => 'text', 'required' => false],
                    ]),
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        if (!Schema::hasTable('loans')) {
            Schema::create('loans', function (Blueprint $table) {
                $table->id();
                $table->string('loan_code')->unique();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('loan_type_id')->nullable()->index();

                $table->decimal('principal_amount', 12, 2)->default(0);
                $table->decimal('interest_rate', 8, 2)->default(0);
                $table->decimal('interest_amount', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->decimal('opening_commission', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('balance_amount', 12, 2)->default(0);

                $table->enum('payment_frequency', ['semanal', 'quincenal', 'mensual'])->default('semanal');
                $table->integer('term_periods')->default(1);
                $table->decimal('installment_amount', 12, 2)->default(0);
                $table->date('start_date');
                $table->date('due_date')->nullable();

                $table->json('type_specific_data')->nullable();
                $table->string('video_path')->nullable();
                $table->string('promissory_note_path')->nullable();

                $table->enum('status', ['activo', 'mora', 'liquidado', 'cerrado_manual', 'cancelado'])->default('activo')->index();
                $table->timestamp('closed_manually_at')->nullable();
                $table->unsignedBigInteger('closed_by')->nullable();
                $table->text('closing_reason')->nullable();
                $table->decimal('closing_final_paid', 12, 2)->nullable();

                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('loan_type_id')->references('id')->on('loan_types')->nullOnDelete();
                $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('loan_installments')) {
            Schema::create('loan_installments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('loan_id')->index();
                $table->integer('installment_number');
                $table->date('scheduled_date')->index();
                $table->dateTime('paid_date')->nullable();
                $table->decimal('scheduled_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('late_fee', 12, 2)->default(0);
                $table->enum('status', ['pendiente', 'parcial', 'pagado', 'vencido', 'cerrado_manual'])->default('pendiente')->index();
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->foreign('loan_id')->references('id')->on('loans')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('loan_payments')) {
            Schema::create('loan_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('loan_id')->index();
                $table->unsignedBigInteger('installment_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('pos_transaction_id')->nullable()->index();

                $table->string('payment_code')->unique();
                $table->enum('payment_type', ['comision_apertura', 'abono', 'cierre_manual'])->default('abono');
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('payment_method', 50)->default('efectivo');
                $table->json('payment_breakdown')->nullable();
                $table->date('scheduled_date')->nullable();
                $table->dateTime('paid_at');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('loan_id')->references('id')->on('loans')->cascadeOnDelete();
                $table->foreign('installment_id')->references('id')->on('loan_installments')->nullOnDelete();
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
        Schema::dropIfExists('loan_installments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('loan_types');
    }
};

