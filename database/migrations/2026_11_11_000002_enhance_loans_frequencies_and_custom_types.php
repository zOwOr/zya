<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modify payment_frequency column to varchar(50) in loans and loan_types
        // so that it supports '12_26', 'quincenal', 'semanal', 'mensual'
        DB::statement("ALTER TABLE loans MODIFY payment_frequency VARCHAR(50) NOT NULL DEFAULT 'semanal'");
        DB::statement("ALTER TABLE loan_types MODIFY default_frequency VARCHAR(50) NOT NULL DEFAULT 'semanal'");

        // Seed / Ensure predefined editable types with the rates Esme handles: 20%, 40%, 50%, 60%
        $now = now();
        $presets = [
            [
                'name' => 'Préstamo Tasa 20%',
                'slug' => 'tasa-20',
                'description' => 'Préstamo predefinido al 20% de interés.',
                'default_interest_rate' => 20.00,
                'default_opening_fee' => 150.00,
                'requires_collateral' => false,
                'default_frequency' => 'semanal',
                'default_periods' => 8,
                'required_fields' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Préstamo Tasa 40%',
                'slug' => 'tasa-40',
                'description' => 'Préstamo predefinido al 40% de interés.',
                'default_interest_rate' => 40.00,
                'default_opening_fee' => 150.00,
                'requires_collateral' => false,
                'default_frequency' => 'quincenal',
                'default_periods' => 8,
                'required_fields' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Préstamo Tasa 50%',
                'slug' => 'tasa-50',
                'description' => 'Préstamo predefinido al 50% de interés.',
                'default_interest_rate' => 50.00,
                'default_opening_fee' => 150.00,
                'requires_collateral' => false,
                'default_frequency' => 'quincenal',
                'default_periods' => 8,
                'required_fields' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Préstamo Tasa 60%',
                'slug' => 'tasa-60',
                'description' => 'Préstamo predefinido al 60% de interés.',
                'default_interest_rate' => 60.00,
                'default_opening_fee' => 150.00,
                'requires_collateral' => false,
                'default_frequency' => '12_26',
                'default_periods' => 8,
                'required_fields' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($presets as $preset) {
            $exists = DB::table('loan_types')
                ->where('slug', $preset['slug'])
                ->orWhere('default_interest_rate', $preset['default_interest_rate'])
                ->exists();

            if (!$exists) {
                DB::table('loan_types')->insert($preset);
            }
        }
    }

    public function down(): void
    {
        // Revert columns if needed
        DB::statement("ALTER TABLE loans MODIFY payment_frequency ENUM('semanal','quincenal','mensual') NOT NULL DEFAULT 'semanal'");
        DB::statement("ALTER TABLE loan_types MODIFY default_frequency ENUM('semanal','quincenal','mensual') NOT NULL DEFAULT 'semanal'");
    }
};

