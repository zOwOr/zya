<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\UnifiedPosController;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PosHardwareScriptDeliveryTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeCashierWithPermissions(array $permissions): User
    {
        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        $branch = Branch::firstOrCreate(
            ['name' => 'Allende'],
            ['address' => 'Centro', 'phone' => '8261000001', 'is_active' => true]
        );

        $role = Role::create(['name' => 'DrawerTestRole_' . uniqid(), 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        $user = User::create([
            'branch_id' => $branch->id,
            'name' => 'Cajero Hardware Test',
            'username' => 'hw_' . uniqid(),
            'email' => 'hw_' . uniqid() . '@test.local',
            'password' => bcrypt('secret123'),
        ]);
        $user->assignRole($role);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return $user;
    }

    public function test_hardware_client_script_routes_serve_valid_js_without_auth_and_support_etag(): void
    {
        $src = UnifiedPosController::hardwareClientSrc();
        $this->assertStringStartsWith('/central-pos/hardware-client?v=', $src);
        $this->assertStringNotContainsString('http://', $src);
        $this->assertStringNotContainsString('https://', $src);

        $response = $this->get('/central-pos/hardware-client');
        $response->assertOk();
        $this->assertStringContainsString('javascript', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('ZyaPosHardware', $response->getContent());
        $this->assertStringContainsString('manualOpenUsbDrawer', $response->getContent());
        $this->assertStringContainsString('getLastError', $response->getContent());

        $etag = $response->headers->get('ETag');
        $this->assertNotEmpty($etag);

        $cachedResponse = $this->withHeaders(['If-None-Match' => $etag])->get('/central-pos/hardware-client');
        $cachedResponse->assertStatus(304);

        // Limpiar headers persistentes de TestCase antes de probar la ruta de respaldo
        $this->flushHeaders();

        // Ruta de respaldo si algún navegador o HTML cacheado solicita /assets/js/pos-hardware.js
        $legacyFallback = $this->get('/assets/js/pos-hardware.js');
        $legacyFallback->assertOk();
        $this->assertStringContainsString('ZyaPosHardware', $legacyFallback->getContent());
    }

    public function test_drawer_views_render_relative_hardware_endpoints_and_enforce_permissions(): void
    {
        $unauthorized = $this->makeCashierWithPermissions(['product.menu', 'product.read']);
        $this->actingAs($unauthorized)->get(route('central-pos.open-drawer'))->assertStatus(403);
        $this->actingAs($unauthorized)->get(route('cash.openDrawer'))->assertStatus(403);

        $authorized = $this->makeCashierWithPermissions([
            'cobros.menu',
            'cobros.read',
            'cobros.create',
            'cobros.open_drawer',
            'cash.menu',
            'cash.read',
            'cash.open_drawer',
            'cash.pre_cut',
        ]);

        $expectedSrc = UnifiedPosController::hardwareClientSrc();

        // 1. Ventana emergente de configuración de cajón (POS y Caja)
        $openDrawerResp = $this->actingAs($authorized)->get(route('central-pos.open-drawer'));
        $openDrawerResp->assertOk()
            ->assertSee($expectedSrc, false)
            ->assertSee('Controlador no cargado', false)
            ->assertSee('btnKickUsb', false)
            ->assertSee('btnSwitchUsbPort', false);

        $this->actingAs($authorized)->get(route('cash.openDrawer'))
            ->assertOk()
            ->assertSee($expectedSrc, false);

        // 2. POS Central 4-en-1: ruta relativa same-origin para popup y script sin 404 estático
        $posResp = $this->actingAs($authorized)->get(route('central-pos.index'));
        $posResp->assertOk()
            ->assertSee($expectedSrc, false)
            ->assertSee("window.open('/central-pos/open-drawer'", false)
            ->assertSee('zyaOpenCashDrawerManual', false)
            ->assertSee('autoOpenUsbDrawerBounded(1200)', false);

        // 3. Caja y Pre-Corte Diario: usan rutas relativas same-origin para hardware y popup
        $cashResp = $this->actingAs($authorized)->get(route('cash.index'));
        $cashResp->assertOk()
            ->assertSee($expectedSrc, false)
            ->assertSee("window.open('/cash/open-drawer'", false)
            ->assertSee('zyaOpenCashDrawerManual', false);

        $dailyCutResp = $this->actingAs($authorized)->get(route('cash.dailyCut'));
        $dailyCutResp->assertOk()
            ->assertSee($expectedSrc, false)
            ->assertSee("window.open('/cash/open-drawer'", false)
            ->assertSee('zyaOpenCashDrawerManual', false);
    }
}
