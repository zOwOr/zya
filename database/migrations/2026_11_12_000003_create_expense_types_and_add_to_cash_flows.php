<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expense_types')) {
            Schema::create('expense_types', function (Blueprint $table) {
                $table->id();
                $table->string('name', 160);
                $table->string('examples', 255)->nullable();
                $table->string('slug', 180)->unique();
                $table->boolean('is_other')->default(false)->index();
                $table->boolean('requires_reason')->default(false);
                $table->unsignedInteger('sort_order')->default(100)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        Schema::table('cash_flows', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_flows', 'expense_type_id')) {
                $table->unsignedBigInteger('expense_type_id')->nullable()->after('module')->index();
            }
            if (!Schema::hasColumn('cash_flows', 'expense_type_name')) {
                $table->string('expense_type_name', 160)->nullable()->after('expense_type_id');
            }
        });

        $now = now();
        $defaults = [
            ['sort' => 1, 'name' => 'Mobiliario y equipo', 'examples' => 'silla, vitrina, impresora', 'slug' => 'mobiliario-y-equipo'],
            ['sort' => 2, 'name' => 'Limpieza', 'examples' => 'jabón, Pinol, papel higiénico', 'slug' => 'limpieza'],
            ['sort' => 3, 'name' => 'Papelería', 'examples' => 'hojas de máquina, clips, grapas', 'slug' => 'papeleria'],
            ['sort' => 4, 'name' => 'Servicios', 'examples' => 'luz, agua, internet', 'slug' => 'servicios'],
            ['sort' => 5, 'name' => 'Renta del local', 'examples' => null, 'slug' => 'renta-del-local'],
            ['sort' => 6, 'name' => 'Mercancía', 'examples' => 'celulares, fundas, micas', 'slug' => 'mercancia'],
            ['sort' => 7, 'name' => 'Refacciones', 'examples' => 'pantallas, baterías', 'slug' => 'refacciones'],
            ['sort' => 8, 'name' => 'Transporte', 'examples' => 'gasolina, camión, taxi', 'slug' => 'transporte'],
            ['sort' => 9, 'name' => 'Envíos y paquetería', 'examples' => null, 'slug' => 'envios-y-paqueteria'],
            ['sort' => 10, 'name' => 'Alimentos y viáticos', 'examples' => 'comida del personal, hospedaje', 'slug' => 'alimentos-y-viaticos'],
            ['sort' => 11, 'name' => 'Sueldos, adelantos y comisiones', 'examples' => null, 'slug' => 'sueldos-adelantos-y-comisiones'],
            ['sort' => 12, 'name' => 'Mantenimiento del local o equipo', 'examples' => null, 'slug' => 'mantenimiento-del-local-o-equipo'],
            ['sort' => 13, 'name' => 'Publicidad', 'examples' => 'lonas, volantes, anuncios', 'slug' => 'publicidad'],
            ['sort' => 14, 'name' => 'Sistemas y trámites', 'examples' => 'mensualidad del sistema, contador, permisos', 'slug' => 'sistemas-y-tramites'],
            ['sort' => 15, 'name' => 'Retiros y gastos personales', 'examples' => null, 'slug' => 'retiros-y-gastos-personales'],
            ['sort' => 16, 'name' => 'Préstamos internos', 'examples' => null, 'slug' => 'prestamos-internos'],
            ['sort' => 17, 'name' => 'Depósitos y traspasos de efectivo', 'examples' => null, 'slug' => 'depositos-y-traspasos-de-efectivo'],
            ['sort' => 9999, 'name' => 'Otro', 'examples' => 'motivo obligatorio', 'slug' => 'otro', 'is_other' => true, 'requires_reason' => true],
        ];

        foreach ($defaults as $item) {
            $exists = DB::table('expense_types')->where('slug', $item['slug'])->exists();
            if (!$exists) {
                DB::table('expense_types')->insert([
                    'name' => $item['name'],
                    'examples' => $item['examples'],
                    'slug' => $item['slug'],
                    'is_other' => $item['is_other'] ?? false,
                    'requires_reason' => $item['requires_reason'] ?? false,
                    'sort_order' => $item['sort'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('cash_flows', function (Blueprint $table) {
            if (Schema::hasColumn('cash_flows', 'expense_type_name')) {
                $table->dropColumn('expense_type_name');
            }
            if (Schema::hasColumn('cash_flows', 'expense_type_id')) {
                $table->dropColumn('expense_type_id');
            }
        });

        Schema::dropIfExists('expense_types');
    }
};

