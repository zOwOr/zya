<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Gift;
use App\Models\GiftMovement;
use App\Models\GiftStock;
use App\Models\GiftTransfer;
use App\Models\User;
use App\Services\GiftInventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GiftMasterCatalogAndTransferTest extends TestCase
{
    use DatabaseTransactions;

    protected Branch $allende;
    protected Branch $morelos;
    protected Branch $cercado;
    protected User $warehouseAdmin;
    protected User $allendeCashier;
    protected User $morelosCashier;

    protected function setUp(): void
    {
        parent::setUp();

        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        $this->allende = Branch::firstOrCreate(
            ['name' => 'Allende'],
            ['address' => 'Centro Allende', 'phone' => '8261000011', 'is_active' => true, 'auto_cut_enabled' => false]
        );
        $this->morelos = Branch::firstOrCreate(
            ['name' => 'Morelos'],
            ['address' => 'Centro Morelos', 'phone' => '8261000012', 'is_active' => true, 'auto_cut_enabled' => false]
        );
        $this->cercado = Branch::firstOrCreate(
            ['name' => 'Cercado'],
            ['address' => 'Centro Cercado', 'phone' => '8181000013', 'is_active' => true, 'auto_cut_enabled' => false]
        );

        foreach ([
            'gifts.menu',
            'gifts.read',
            'gifts.create',
            'gifts.transfer',
            'gifts.deliver',
            'gifts.edit',
            'gifts.delete',
            'gifts.all_branches',
        ] as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'AdminGiftsTest', 'guard_name' => 'web']);
        $adminRole->syncPermissions([
            'gifts.menu',
            'gifts.read',
            'gifts.create',
            'gifts.transfer',
            'gifts.deliver',
            'gifts.edit',
            'gifts.delete',
            'gifts.all_branches',
        ]);

        $branchRole = Role::firstOrCreate(['name' => 'SucursalGiftsTest', 'guard_name' => 'web']);
        $branchRole->syncPermissions([
            'gifts.menu',
            'gifts.read',
            'gifts.deliver',
        ]);

        $this->warehouseAdmin = User::factory()->create([
            'branch_id' => $this->allende->id,
            'email' => 'gifts.admin.' . uniqid() . '@zya.test',
        ]);
        $this->warehouseAdmin->assignRole($adminRole);

        $this->allendeCashier = User::factory()->create([
            'branch_id' => $this->allende->id,
            'email' => 'gifts.allende.' . uniqid() . '@zya.test',
        ]);
        $this->allendeCashier->assignRole($branchRole);

        $this->morelosCashier = User::factory()->create([
            'branch_id' => $this->morelos->id,
            'email' => 'gifts.morelos.' . uniqid() . '@zya.test',
        ]);
        $this->morelosCashier->assignRole($branchRole);
    }

    public function test_master_catalog_prevents_duplicates_allows_central_entry_transfers_and_branch_delivery(): void
    {
        // 1. Alta única en Catálogo Maestro (Inventario General)
        $createResp = $this->actingAs($this->warehouseAdmin)->post(route('gifts.store'), [
            'name' => 'Gorra PayJoy Edición',
            'brand' => 'PayJoy',
            'supplier_name' => 'PayJoy Oficial',
            'stock_quantity' => 20,
            'min_stock' => 3,
            'unit_cost' => 45,
        ]);
        $createResp->assertSessionHasNoErrors();

        $masterGift = Gift::where('normalized_name', Gift::normalizeName('Gorra PayJoy Edición'))->firstOrFail();
        $this->assertNull($masterGift->branch_id);
        $this->assertSame(20, (int) $masterGift->stock_quantity);

        // Rechaza duplicado con distintas mayúsculas y espacios extra
        $dupResp = $this->actingAs($this->warehouseAdmin)->post(route('gifts.store'), [
            'name' => '  gorra   payjoy   edición ',
            'stock_quantity' => 10,
        ]);
        $dupResp->assertSessionHasErrors('name');
        $this->assertSame(
            1,
            Gift::where('normalized_name', Gift::normalizeName('Gorra PayJoy Edición'))->count()
        );

        // 2. Entrada al Inventario General (+10 => 30)
        $entryResp = $this->actingAs($this->warehouseAdmin)->post(route('gifts.entry'), [
            'gift_id' => $masterGift->id,
            'quantity' => 10,
            'reference_code' => 'REM-1001',
        ]);
        $entryResp->assertSessionHasNoErrors();
        $this->assertSame(30, (int) $masterGift->fresh()->stock_quantity);

        // 3. Validación estricta: no permite traspasar más piezas de las disponibles (35 > 30)
        $overTransferResp = $this->actingAs($this->warehouseAdmin)->post(route('gifts.transfer'), [
            'gift_id' => $masterGift->id,
            'to_branch_id' => $this->allende->id,
            'quantity' => 35,
        ]);
        $overTransferResp->assertSessionHasErrors('quantity');
        $this->assertSame(30, (int) $masterGift->fresh()->stock_quantity);
        $this->assertSame(0, $masterGift->fresh()->stockInBranch($this->allende->id));

        // 4. Traspaso válido de Inventario General -> Allende (12 pzas) y Morelos (5 pzas)
        $trAllende = $this->actingAs($this->warehouseAdmin)->post(route('gifts.transfer'), [
            'gift_id' => $masterGift->id,
            'to_branch_id' => $this->allende->id,
            'quantity' => 12,
            'notes' => 'Dotación inicial Allende',
        ]);
        $trAllende->assertSessionHasNoErrors();

        $trMorelos = $this->actingAs($this->warehouseAdmin)->post(route('gifts.transfer'), [
            'gift_id' => $masterGift->id,
            'to_branch_id' => $this->morelos->id,
            'quantity' => 5,
            'notes' => 'Dotación inicial Morelos',
        ]);
        $trMorelos->assertSessionHasNoErrors();

        $masterGift->refresh();
        $this->assertSame(13, (int) $masterGift->stock_quantity); // 30 - 12 - 5
        $this->assertSame(12, $masterGift->stockInBranch($this->allende->id));
        $this->assertSame(5, $masterGift->stockInBranch($this->morelos->id));
        $this->assertSame(0, $masterGift->stockInBranch($this->cercado->id));

        $transferLog = GiftTransfer::where('gift_id', $masterGift->id)->where('to_branch_id', $this->allende->id)->first();
        $this->assertNotNull($transferLog);
        $this->assertNull($transferLog->from_branch_id);
        $this->assertSame(30, (int) $transferLog->from_previous_stock);
        $this->assertSame(18, (int) $transferLog->from_new_stock);
        $this->assertSame(0, (int) $transferLog->to_previous_stock);
        $this->assertSame(12, (int) $transferLog->to_new_stock);

        // 5. Entrega en Allende descuenta EXCLUSIVAMENTE de Allende
        $deliverAllende = $this->actingAs($this->allendeCashier)->post(route('gifts.deliver'), [
            'gift_id' => $masterGift->id,
            'quantity' => 4,
            'customer_name' => 'Cliente Allende Prueba',
        ]);
        $deliverAllende->assertSessionHasNoErrors();

        $masterGift->refresh();
        $this->assertSame(8, $masterGift->stockInBranch($this->allende->id));
        $this->assertSame(5, $masterGift->stockInBranch($this->morelos->id));
        $this->assertSame(13, (int) $masterGift->stock_quantity); // General intacto

        // Cajero de Morelos no puede entregar más de su stock (6 > 5) aunque Allende/General tengan stock
        $overDeliverMorelos = $this->actingAs($this->morelosCashier)->post(route('gifts.deliver'), [
            'gift_id' => $masterGift->id,
            'quantity' => 6,
            'customer_name' => 'Cliente Morelos Prueba',
        ]);
        $overDeliverMorelos->assertSessionHasErrors('quantity');
        $this->assertSame(5, $masterGift->fresh()->stockInBranch($this->morelos->id));
    }

    public function test_consolidate_existing_gifts_merges_legacy_duplicates_without_losing_branch_stocks(): void
    {
        $suffix = uniqid();
        $rawNameA = "Gorra Histórica {$suffix}";
        $rawNameB = "  gorra   histórica {$suffix} ";

        $legacyAllendeId = DB::table('gifts')->insertGetId([
            'code' => 'LEG-A-' . $suffix,
            'name' => $rawNameA,
            'normalized_name' => null,
            'branch_id' => $this->allende->id,
            'stock_quantity' => 6,
            'min_stock' => 2,
            'unit_cost' => 30,
            'status' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $legacyMorelosId = DB::table('gifts')->insertGetId([
            'code' => 'LEG-M-' . $suffix,
            'name' => $rawNameB,
            'normalized_name' => null,
            'branch_id' => $this->morelos->id,
            'stock_quantity' => 9,
            'min_stock' => 2,
            'unit_cost' => 30,
            'status' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $movId = DB::table('gift_movements')->insertGetId([
            'gift_id' => $legacyMorelosId,
            'branch_id' => null,
            'user_id' => $this->warehouseAdmin->id,
            'movement_type' => 'entrada_proveedor',
            'quantity' => 9,
            'previous_stock' => 0,
            'new_stock' => 9,
            'movement_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        GiftInventoryService::consolidateExistingGifts();

        $normalized = Gift::normalizeName($rawNameA);
        $masters = Gift::where('normalized_name', $normalized)->get();
        $this->assertCount(1, $masters);

        $master = $masters->first();
        $this->assertSame($legacyAllendeId, $master->id);
        $this->assertNull($master->branch_id);
        $this->assertSame(0, (int) $master->stock_quantity);
        $this->assertSame(6, $master->stockInBranch($this->allende->id));
        $this->assertSame(9, $master->stockInBranch($this->morelos->id));
        $this->assertNull(Gift::find($legacyMorelosId));

        $relocatedMov = GiftMovement::findOrFail($movId);
        $this->assertSame($master->id, (int) $relocatedMov->gift_id);
        $this->assertSame($this->morelos->id, (int) $relocatedMov->branch_id);

        // Idempotencia: segunda ejecución conserva exactamente las mismas existencias
        GiftInventoryService::consolidateExistingGifts();
        $master->refresh();
        $this->assertSame(6, $master->stockInBranch($this->allende->id));
        $this->assertSame(9, $master->stockInBranch($this->morelos->id));
    }
}

