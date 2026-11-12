<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinBrand;
use App\Models\FinDevice;
use App\Models\FinFinanciera;
use App\Models\FinSale;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Product;
use App\Models\Tanda;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnifiedPosRolesAndPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $cajeraAllende;
    protected User $restrictedUser;
    protected User $unauthorizedUser;
    protected Branch $branchAllende;
    protected Branch $branchMorelos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchAllende = Branch::firstOrCreate(
            ['name' => 'Allende'],
            ['address' => 'Allende Centro', 'phone' => '8261234567', 'is_active' => true]
        );
        $this->branchMorelos = Branch::firstOrCreate(
            ['name' => 'Montemorelos'],
            ['address' => 'Montemorelos Centro', 'phone' => '8267654321', 'is_active' => true]
        );

        $superRole = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $this->superAdmin = User::create([
            'name' => 'Esmeralda Zapata',
            'username' => 'esme_' . uniqid(),
            'email' => 'esme_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $this->superAdmin->assignRole($superRole);

        // Vendedora con rol Admin habitual (Verónica en Allende)
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $this->cajeraAllende = User::create([
            'name' => 'Verónica Acosta',
            'username' => 'vero_' . uniqid(),
            'email' => 'vero_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $this->cajeraAllende->assignRole($adminRole);

        // Rol granular: Solo Venta de Productos (sin préstamos ni financieras ni tandas)
        $roleSoloProductos = Role::create(['name' => 'SoloProductos_' . uniqid(), 'guard_name' => 'web']);
        $roleSoloProductos->givePermissionTo([
            'cobros.menu',
            'cobros.read',
            'cobros.create',
            'cobros.productos',
        ]);
        $this->restrictedUser = User::create([
            'name' => 'Cajera Solo Productos',
            'username' => 'prod_' . uniqid(),
            'email' => 'prod_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $this->restrictedUser->assignRole($roleSoloProductos);

        // Usuario sin permisos de POS
        $roleSinPos = Role::create(['name' => 'SinPos_' . uniqid(), 'guard_name' => 'web']);
        $roleSinPos->givePermissionTo(['customer.read']);
        $this->unauthorizedUser = User::create([
            'name' => 'Usuario Sin Acceso POS',
            'username' => 'nopos_' . uniqid(),
            'email' => 'nopos_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'branch_id' => $this->branchAllende->id,
        ]);
        $this->unauthorizedUser->assignRole($roleSinPos);
    }

    /**
     * Test 1: SuperAdmin y Admin con permisos completos tienen acceso total a las 4 secciones.
     */
    public function test_superadmin_and_admin_have_full_access_to_pos_4_in_1(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('central-pos.index'));
        $response->assertStatus(200);

        $content = $response->getContent();
        // Las 4 pestañas deben estar presentes
        $this->assertStringContainsString('1. Financieras (TAG / Device ID)', $content);
        $this->assertStringContainsString('2. Préstamos', $content);
        $this->assertStringContainsString('3. Tandas', $content);
        $this->assertStringContainsString('4. Productos (Stock y Libre)', $content);

        // El selector de sucursal debe estar disponible para SuperAdmin
        $this->assertStringContainsString('name="branch_id"', $content);
        $this->assertStringContainsString('Abrir Cajón Físico', $content);
    }

    /**
     * Test 2: Usuario con permisos restringidos solo ve la sección permitida (Productos) y no las otras 3.
     */
    public function test_restricted_role_only_sees_permitted_section(): void
    {
        $this->actingAs($this->restrictedUser);

        $response = $this->get(route('central-pos.index'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // Debe ver la sección 4 (Productos)
        $this->assertStringContainsString('4. Productos (Stock y Libre)', $content);
        $this->assertStringContainsString('id="sec-productos"', $content);

        // NO debe ver las secciones 1, 2 ni 3
        $this->assertStringNotContainsString('1. Financieras (TAG / Device ID)', $content);
        $this->assertStringNotContainsString('id="sec-financieras"', $content);
        $this->assertStringNotContainsString('2. Préstamos', $content);
        $this->assertStringNotContainsString('id="sec-prestamos"', $content);
        $this->assertStringNotContainsString('3. Tandas', $content);
        $this->assertStringNotContainsString('id="sec-tandas"', $content);

        // Al no tener cobros.all_branches, NO debe ver el selector desplegable de cambio de sucursal
        $this->assertStringNotContainsString('<select name="branch_id"', $content);
    }

    /**
     * Test 3: Endpoints de búsqueda respetan permisos granulares (403 si no tiene permiso).
     */
    public function test_lookup_endpoints_enforce_granular_permissions(): void
    {
        $this->actingAs($this->restrictedUser);

        // 1. Buscar productos: SÍ TIENE PERMISO
        $prodRes = $this->getJson(route('central-pos.lookup-products', ['q' => 'Mica']));
        $prodRes->assertStatus(200);

        // 2. Buscar financieras: NO TIENE PERMISO -> 403
        $finRes = $this->getJson(route('central-pos.lookup-financiera', ['q' => 'PayJoy']));
        $finRes->assertStatus(403);

        // 3. Buscar préstamos: NO TIENE PERMISO -> 403
        $loanRes = $this->getJson(route('central-pos.lookup-loans', ['customer_id' => 1]));
        $loanRes->assertStatus(403);

        // 4. Buscar tandas: NO TIENE PERMISO -> 403
        $tandaRes = $this->getJson(route('central-pos.lookup-tanda'));
        $tandaRes->assertStatus(403);
    }

    /**
     * Test 4: Checkout valida permisos por tipo de ítem y rechaza secciones no autorizadas.
     */
    public function test_checkout_rejects_unauthorized_section_items(): void
    {
        $this->actingAs($this->restrictedUser);

        // Intentar pagar un abono de préstamo teniendo solo permisos de productos
        $payloadUnauthorized = [
            'branch_id' => $this->branchAllende->id,
            'customer_name' => 'Cliente Prueba',
            'payment_method' => 'efectivo',
            'cash_amount' => 500.00,
            'paid_amount' => 500.00,
            'items' => [
                [
                    'section' => 'prestamo_abono',
                    'loan_id' => 999,
                    'title' => 'Abono Cuota #1',
                    'quantity' => 1,
                    'amount' => 500.00,
                    'commission' => 0.00,
                ],
            ],
        ];

        $response = $this->postJson(route('central-pos.checkout'), $payloadUnauthorized);
        $response->assertStatus(403);
        $this->assertStringContainsString('No tienes permisos para crear o abonar Préstamos', $response->json('message'));

        // Checkout de producto permitido -> SÍ funciona
        $payloadAuthorized = [
            'branch_id' => $this->branchAllende->id,
            'customer_name' => 'Cliente Prueba',
            'payment_method' => 'efectivo',
            'cash_amount' => 150.00,
            'paid_amount' => 150.00,
            'items' => [
                [
                    'section' => 'producto_dinamico',
                    'title' => 'Funda Uso Rudo',
                    'quantity' => 1,
                    'amount' => 150.00,
                    'commission' => 0.00,
                ],
            ],
        ];

        $responseOk = $this->postJson(route('central-pos.checkout'), $payloadAuthorized);
        $responseOk->assertStatus(200);
        $this->assertTrue($responseOk->json('success'));
    }

    /**
     * Test 5: Usuario sin ningún permiso de POS es bloqueado con 403.
     */
    public function test_unauthorized_user_is_blocked_from_pos_central(): void
    {
        $this->actingAs($this->unauthorizedUser);

        $response = $this->get(route('central-pos.index'));
        $response->assertStatus(403);
    }
}

