<?php

namespace Tests\Unit;

use App\Http\Controllers\Dashboard\GiftController;
use App\Models\Branch;
use App\Models\Gift;
use App\Models\GiftStock;
use App\Models\GiftTransfer;
use App\Services\GiftInventoryService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use Tests\TestCase;

class GiftInventoryServiceTest extends TestCase
{
    public function test_gift_name_normalization_prevents_whitespace_and_case_duplicates(): void
    {
        $this->assertSame('gorra payjoy', Gift::normalizeName('Gorra PayJoy'));
        $this->assertSame('gorra payjoy', Gift::normalizeName("  GORRA   payjoy \t "));
        $this->assertSame('Gorra PayJoy', GiftInventoryService::cleanName('   Gorra    PayJoy   '));
        $this->assertSame('taza edición térmica', Gift::normalizeName('  Taza   Edición  Térmica '));
    }

    public function test_gift_model_stock_helpers_isolate_central_and_branch_stocks(): void
    {
        $gift = new Gift([
            'code' => 'GFT-PAYJOY',
            'name' => 'Gorra PayJoy',
            'stock_quantity' => 25,
            'min_stock' => 3,
            'status' => 'activo',
        ]);
        $gift->id = 10;

        $allende = new Branch(['name' => 'Allende']);
        $allende->id = 1;
        $morelos = new Branch(['name' => 'Morelos']);
        $morelos->id = 2;

        $stockAllende = new GiftStock(['gift_id' => 10, 'branch_id' => 1, 'stock_quantity' => 8, 'min_stock' => 2]);
        $stockAllende->setRelation('branch', $allende);
        $stockMorelos = new GiftStock(['gift_id' => 10, 'branch_id' => 2, 'stock_quantity' => 5, 'min_stock' => 2]);
        $stockMorelos->setRelation('branch', $morelos);

        $gift->setRelation('stocks', collect([$stockAllende, $stockMorelos]));

        $this->assertSame(25, $gift->central_stock);
        $this->assertSame(25, $gift->stockInBranch(null));
        $this->assertSame(8, $gift->stockInBranch(1));
        $this->assertSame(5, $gift->stockInBranch(2));
        $this->assertSame(0, $gift->stockInBranch(99));
        $this->assertSame(13, $gift->total_branch_stock);
        $this->assertSame(38, $gift->total_global_stock);
    }

    public function test_gift_transfer_accessors_format_origin_and_destination_labels(): void
    {
        $cercado = new Branch(['name' => 'Cercado']);
        $cercado->id = 3;

        $centralTransfer = new GiftTransfer([
            'transfer_code' => 'TRF-GFT-001',
            'from_branch_id' => null,
            'to_branch_id' => 3,
            'quantity' => 6,
        ]);
        $centralTransfer->setRelation('toBranch', $cercado);

        $this->assertSame('Inventario General (Almacén Central)', $centralTransfer->origin_label);
        $this->assertSame('Cercado', $centralTransfer->destination_label);

        $allende = new Branch(['name' => 'Allende']);
        $allende->id = 1;
        $branchTransfer = new GiftTransfer([
            'transfer_code' => 'TRF-GFT-002',
            'from_branch_id' => 1,
            'to_branch_id' => 3,
            'quantity' => 2,
        ]);
        $branchTransfer->setRelation('fromBranch', $allende);
        $branchTransfer->setRelation('toBranch', $cercado);

        $this->assertSame('Allende', $branchTransfer->origin_label);
        $this->assertSame('Cercado', $branchTransfer->destination_label);
    }

    public function test_transfer_and_delivery_preconditions_throw_validation_exception(): void
    {
        try {
            GiftInventoryService::transfer(1, null, 2, 0);
            $this->fail('Se esperaba ValidationException por cantidad <= 0.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('quantity', $e->errors());
        }

        try {
            GiftInventoryService::transfer(1, 2, 2, 5);
            $this->fail('Se esperaba ValidationException por origen igual al destino.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('to_branch_id', $e->errors());
        }

        try {
            GiftInventoryService::deliverFromBranch(1, 0, 1);
            $this->fail('Se esperaba ValidationException por sucursal inválida.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('branch_id', $e->errors());
        }
    }

    public function test_gifts_routes_permission_mapping_and_blade_views_compile_cleanly(): void
    {
        $this->assertTrue(Route::has('gifts.transfer'));

        $ref = new ReflectionClass(GiftController::class);
        $prop = $ref->getProperty('permissionMapping');
        $prop->setAccessible(true);
        $mapping = $prop->getValue($ref->newInstanceWithoutConstructor());
        $this->assertSame('transfer', $mapping['transfer'] ?? null);
        $this->assertSame('deliver', $mapping['registerDelivery'] ?? null);

        $giftsViewPath = resource_path('views/gifts/index.blade.php');
        $giftsSource = file_get_contents($giftsViewPath);
        $this->assertIsString($giftsSource);
        $compiledGifts = Blade::compileString($giftsSource);
        $this->assertStringContainsString("route('gifts.transfer')", $giftsSource);
        $this->assertStringContainsString("route('gifts.entry')", $giftsSource);
        $this->assertStringContainsString("route('gifts.deliver')", $giftsSource);
        $this->assertStringContainsString('modalTransferGift', $giftsSource);
        $this->assertStringContainsString('select2-autocomplete', $giftsSource);
        $this->assertNotEmpty($compiledGifts);

        $posViewPath = resource_path('views/central-pos/index.blade.php');
        $posSource = file_get_contents($posViewPath);
        $this->assertIsString($posSource);
        $this->assertStringContainsString('stockInBranch($branchId)', $posSource);
    }
}

