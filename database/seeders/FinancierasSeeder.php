<?php

namespace Database\Seeders;

use App\Models\FinBrand;
use App\Models\FinFinanciera;
use App\Models\FinWarrantyStage;
use Illuminate\Database\Seeder;

class FinancierasSeeder extends Seeder
{
    public function run(): void
    {
        // Financieras iniciales
        $financieras = [
            ['name' => 'PayJoy', 'contact_name' => 'Atención PayJoy', 'contact_phone' => '800-000-0001', 'contact_email' => 'soporte@payjoy.com'],
            ['name' => 'Macropay', 'contact_name' => 'Mesa Macropay', 'contact_phone' => '800-000-0002', 'contact_email' => 'contacto@macropay.com'],
            ['name' => 'Kueski Pay', 'contact_name' => 'Soporte Kueski', 'contact_phone' => '800-000-0003', 'contact_email' => 'soporte@kueskipay.com'],
            ['name' => 'CrediFácil', 'contact_name' => 'CrediFácil Ventas', 'contact_phone' => '800-000-0004', 'contact_email' => 'ventas@credifacil.com'],
        ];

        foreach ($financieras as $f) {
            FinFinanciera::firstOrCreate(['name' => $f['name']], $f);
        }

        // Marcas iniciales
        $brands = [
            'Samsung',
            'Apple',
            'Xiaomi',
            'Motorola',
            'Huawei',
            'Oppo',
            'Honor',
            'ZTE',
            'Realme',
            'Infinix',
        ];

        foreach ($brands as $b) {
            FinBrand::firstOrCreate(['name' => $b]);
        }

        // Etapas de Garantía
        $stages = [
            ['name' => 'Recepción', 'order' => 1, 'color' => '#17a2b8', 'is_final' => false],
            ['name' => 'En Diagnóstico', 'order' => 2, 'color' => '#ffc107', 'is_final' => false],
            ['name' => 'En Reparación / En Taller', 'order' => 3, 'color' => '#fd7e14', 'is_final' => false],
            ['name' => 'Listo para Entrega', 'order' => 4, 'color' => '#20c997', 'is_final' => false],
            ['name' => 'Entregado al Cliente (Resuelto)', 'order' => 5, 'color' => '#28a745', 'is_final' => true],
            ['name' => 'Garantía Rechazada', 'order' => 6, 'color' => '#dc3545', 'is_final' => true],
        ];

        foreach ($stages as $s) {
            FinWarrantyStage::firstOrCreate(['name' => $s['name']], $s);
        }
    }
}
