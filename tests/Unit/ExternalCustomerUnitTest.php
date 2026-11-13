<?php

namespace Tests\Unit;

use App\Http\Controllers\Dashboard\CashController;
use App\Models\CashFlow;
use App\Models\ExternalCustomer;
use App\Models\Permission;
use App\Models\PosTransaction;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class ExternalCustomerUnitTest extends TestCase
{
    public function test_pos_transaction_detects_financiera_concepts_conditionally(): void
    {
        $this->assertTrue(PosTransaction::isFinancieraSection('financiera_abono'));
        $this->assertTrue(PosTransaction::isFinancieraSection('financiera_externa'));
        $this->assertFalse(PosTransaction::isFinancieraSection('producto_inventario'));
        $this->assertFalse(PosTransaction::isFinancieraSection('producto_dinamico'));
        $this->assertFalse(PosTransaction::isFinancieraSection('prestamo_abono'));
        $this->assertFalse(PosTransaction::isFinancieraSection('tanda_abono'));
        $this->assertFalse(PosTransaction::isFinancieraSection('gift'));

        $productOnlyCart = [
            ['section' => 'producto_inventario', 'title' => 'Funda iPhone', 'amount' => 150],
            ['section' => 'gift', 'title' => 'Mica Regalo', 'amount' => 0],
        ];
        $this->assertFalse(PosTransaction::containsFinancieraConcept($productOnlyCart));

        $mixedWithExternalFinCart = [
            ['section' => 'producto_dinamico', 'title' => 'Cable USB-C', 'amount' => 120],
            ['section' => 'financiera_externa', 'title' => 'Abono PayJoy Externo', 'amount' => 450],
        ];
        $this->assertTrue(PosTransaction::containsFinancieraConcept($mixedWithExternalFinCart));
    }

    public function test_external_customer_maps_required_and_optional_fields_for_internal_promotion(): void
    {
        $ext = new ExternalCustomer([
            'tit_name' => '  Juan Pérez López  ',
            'tit_email' => ' juan.externo@example.com ',
            'tit_phone' => ' 8261112233 ',
            'tit_facebook' => ' facebook.com/juanp ',
            'tit_status' => 'aprobado',
            'tit_city' => 'Allende, N.L.',
            'tit_address' => 'Calle Hidalgo #123 Col. Centro',
            'alternate_phone' => '8269998877',
            'tit_work' => 'Taller Mecánico',
            'position' => 'Técnico',
            'monthly_income' => 14500.50,
            'tit_link_location' => 'https://maps.google.com/?q=25.28,-100.02',
            'status' => ExternalCustomer::STATUS_ACTIVE,
        ]);

        $this->assertTrue($ext->hasRequiredInternalFields());
        $this->assertFalse($ext->isConverted());

        $mapped = $ext->toInternalCustomerAttributes();
        $this->assertSame('Juan Pérez López', $mapped['tit_name']);
        $this->assertSame('juan.externo@example.com', $mapped['tit_email']);
        $this->assertSame('8261112233', $mapped['tit_phone']);
        $this->assertSame('facebook.com/juanp', $mapped['tit_facebook']);
        $this->assertSame('aprobado', $mapped['tit_status']);
        $this->assertSame('Allende, N.L.', $mapped['tit_city']);
        $this->assertSame('Calle Hidalgo #123 Col. Centro', $mapped['tit_address']);
        $this->assertSame('8269998877', $mapped['alternate_phone']);
        $this->assertSame('Taller Mecánico', $mapped['tit_work']);
        $this->assertSame('Técnico', $mapped['position']);
        $this->assertEquals('14500.50', $mapped['monthly_income']);

        $incomplete = new ExternalCustomer([
            'tit_name' => 'Solo Nombre',
            'tit_phone' => '8261000000',
        ]);
        $this->assertFalse($incomplete->hasRequiredInternalFields());

        $converted = new ExternalCustomer([
            'status' => ExternalCustomer::STATUS_CONVERTED,
            'converted_customer_id' => 42,
        ]);
        $this->assertTrue($converted->isConverted());
    }

    public function test_external_customer_validation_rules_match_standard_customer_required_fields(): void
    {
        $rules = ExternalCustomer::validationRules();

        foreach (['tit_name', 'tit_email', 'tit_phone', 'tit_status', 'tit_address', 'tit_facebook', 'tit_city'] as $requiredField) {
            $this->assertArrayHasKey($requiredField, $rules);
            $ruleStr = is_array($rules[$requiredField]) ? implode('|', array_map('strval', $rules[$requiredField])) : (string) $rules[$requiredField];
            $this->assertStringContainsString('required', $ruleStr, "Expected {$requiredField} to be required");
        }
    }

    public function test_cash_flow_and_chronological_operations_preserve_external_customer_traceability(): void
    {
        $ext = new ExternalCustomer([
            'tit_name' => 'María Externa Credimás',
            'tit_phone' => '8261234567',
        ]);
        $ext->id = 77;

        $flow = new CashFlow([
            'type' => 'income',
            'amount' => 380.00,
            'payment_method' => 'efectivo',
            'cash_amount' => 380.00,
            'external_customer_id' => 77,
            'customer_name' => '',
            'description' => 'Cobro Centralizado (MOV-EXT01): Abono Financiera Externa',
            'reference' => 'MOV-EXT01',
            'module' => 'Cobro Centralizado',
        ]);
        $flow->created_at = Carbon::parse('2026-11-13 11:30:00');
        $flow->setRelation('externalCustomer', $ext);

        $this->assertTrue($flow->is_external_customer);
        $this->assertSame('María Externa Credimás', $flow->customer_display_name);

        $controller = $this->app->make(CashController::class);
        $method = new ReflectionMethod(CashController::class, 'buildChronologicalOperations');
        $method->setAccessible(true);

        $ops = $method->invoke($controller, collect([$flow]));
        $this->assertCount(1, $ops);
        $this->assertTrue($ops[0]['is_external_customer']);
        $this->assertSame(77, $ops[0]['external_customer_id']);
        $this->assertSame('María Externa Credimás', $ops[0]['customer_name']);
    }

    public function test_permission_helpers_expose_spanish_labels_for_external_customer_group(): void
    {
        $this->assertSame('Clientes Externos', Permission::groupLabel('external_customer'));
        $this->assertSame('External: Promover / Convertir a Cliente Interno', Permission::defaultAlias('external.convert'));
    }
}
