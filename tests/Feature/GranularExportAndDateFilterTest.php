<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\DailyCut;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GranularExportAndDateFilterTest extends TestCase
{
    use DatabaseTransactions;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        if (app()->bound(PermissionRegistrar::class)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        $this->branch = Branch::firstOrCreate(
            ['name' => 'Allende'],
            ['address' => 'Centro', 'phone' => '8261000001', 'is_active' => true, 'auto_cut_enabled' => false]
        );

        foreach ([
            'dashboard.read',
            'dashboard.export',
            'dashboard.financieras.export_pdf',
            'dashboard.financieras.export_excel',
            'dashboard.ventas.export_pdf',
            'dashboard.ventas.export_excel',
            'dashboard.filter_all_dates',
            'financieras.read',
            'financieras.bitacora.read',
            'financieras.bitacora.export_pdf',
            'financieras.bitacora.export_excel',
            'financieras.ventas.read',
            'financieras.ventas.export',
            'financieras.ventas.export_pdf',
            'financieras.ventas.export_excel',
            'financieras.filter_all_dates',
            'cash.read',
            'cash.export',
            'cash.cortes.export_pdf',
            'cash.cortes.export_excel',
            'cash.gastos.export_pdf',
            'cash.gastos.export_excel',
            'cash.filter_all_dates',
            'orders.read',
            'orders.export',
            'orders.export_pdf',
            'orders.export_excel',
            'orders.filter_all_dates',
        ] as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
            ]);
        }
    }

    protected function makeUserWithPermissions(array $permissions): User
    {
        $user = User::create([
            'name' => 'User ' . uniqid(),
            'username' => 'user_' . uniqid(),
            'email' => 'user_' . uniqid() . '@zyacelulares.com',
            'password' => bcrypt('secret123'),
            'branch_id' => $this->branch->id,
        ]);
        $user->syncPermissions($permissions);

        return $user;
    }

    /**
     * @dataProvider exportPermissionMatrixProvider
     */
    public function test_pdf_and_excel_permissions_are_strictly_isolated_per_section(
        string $baseReadPermission,
        string $pdfRouteName,
        string $excelRouteName,
        string $pdfPermission,
        string $excelPermission,
        string $legacyBroadPermission
    ): void {
        // 1. Usuario con permiso base + legado amplio NO puede exportar PDF ni Excel sin el permiso granular
        $legacyUser = $this->makeUserWithPermissions([$baseReadPermission, $legacyBroadPermission]);
        $this->actingAs($legacyUser)->get(route($pdfRouteName))->assertStatus(403);
        $this->actingAs($legacyUser)->get(route($excelRouteName))->assertStatus(403);

        // 2. Usuario con solo permiso PDF: PDF => 200, Excel => 403
        $pdfOnlyUser = $this->makeUserWithPermissions([$baseReadPermission, $pdfPermission]);
        $this->actingAs($pdfOnlyUser)->get(route($pdfRouteName))->assertStatus(200);
        $this->actingAs($pdfOnlyUser)->get(route($excelRouteName))->assertStatus(403);

        // 3. Usuario con solo permiso Excel: PDF => 403, Excel => 200
        $excelOnlyUser = $this->makeUserWithPermissions([$baseReadPermission, $excelPermission]);
        $this->actingAs($excelOnlyUser)->get(route($pdfRouteName))->assertStatus(403);
        $this->actingAs($excelOnlyUser)->get(route($excelRouteName))->assertStatus(200);
    }

    public static function exportPermissionMatrixProvider(): array
    {
        return [
            'dashboard_financieras' => [
                'dashboard.read',
                'dashboard.financieras.pdf',
                'dashboard.financieras.excel',
                'dashboard.financieras.export_pdf',
                'dashboard.financieras.export_excel',
                'dashboard.export',
            ],
            'dashboard_ventas' => [
                'dashboard.read',
                'dashboard.ventas.pdf',
                'dashboard.ventas.excel',
                'dashboard.ventas.export_pdf',
                'dashboard.ventas.export_excel',
                'dashboard.export',
            ],
            'financieras_bitacora' => [
                'financieras.bitacora.read',
                'financieras.bitacora.export.pdf',
                'financieras.bitacora.export.excel',
                'financieras.bitacora.export_pdf',
                'financieras.bitacora.export_excel',
                'financieras.read',
            ],
            'financieras_ventas' => [
                'financieras.ventas.read',
                'financieras.ventas.export.pdf',
                'financieras.ventas.export.excel',
                'financieras.ventas.export_pdf',
                'financieras.ventas.export_excel',
                'financieras.ventas.export',
            ],
            'cash_cortes' => [
                'cash.read',
                'cash.exportPdf',
                'cash.exportExcel',
                'cash.cortes.export_pdf',
                'cash.cortes.export_excel',
                'cash.export',
            ],
            'cash_gastos' => [
                'cash.read',
                'cash.expenses.pdf',
                'cash.expenses.excel',
                'cash.gastos.export_pdf',
                'cash.gastos.export_excel',
                'cash.export',
            ],
            'orders' => [
                'orders.read',
                'order.exportPdf',
                'order.exportExcel',
                'orders.export_pdf',
                'orders.export_excel',
                'orders.export',
            ],
        ];
    }

    public function test_restricted_user_is_blocked_at_two_day_boundary_on_cash_expenses_and_cuts(): void
    {
        $today = Carbon::today();
        $oldDate = $today->copy()->subDays(5)->toDateString();
        $recentDate = $today->copy()->subDays(1)->toDateString();

        $oldCut = DailyCut::create([
            'branch_id' => $this->branch->id,
            'date' => $oldDate,
            'cut_type' => 'corte_z',
            'cut_mode' => 'manual',
            'user_id' => $this->makeUserWithPermissions([])->id,
            'opening_balance' => 100,
            'total_income' => 500,
            'cash_income' => 500,
            'card_income' => 0,
            'transfer_income' => 0,
            'total_expense' => 0,
            'withdraw_amount' => 0,
            'balance' => 600,
            'declared_cash' => 600,
            'difference_amount' => 0,
            'notes' => 'Corte antiguo restringido TEST_OLD_CUT',
            'closed_at' => Carbon::parse($oldDate . ' 20:00:00'),
        ]);

        CashFlow::create([
            'branch_id' => $this->branch->id,
            'type' => 'egreso',
            'amount' => 123.45,
            'method' => 'efectivo',
            'module' => 'Gastos',
            'reference' => 'EXP-OLD',
            'description' => 'GASTO_ANTIGUO_FUERA_DE_VENTANA',
            'date' => Carbon::parse($oldDate . ' 12:00:00'),
        ]);

        CashFlow::create([
            'branch_id' => $this->branch->id,
            'type' => 'egreso',
            'amount' => 77.00,
            'method' => 'efectivo',
            'module' => 'Gastos',
            'reference' => 'EXP-RECENT',
            'description' => 'GASTO_RECIENTE_DENTRO_DE_VENTANA',
            'date' => Carbon::parse($recentDate . ' 12:00:00'),
        ]);

        $restrictedUser = $this->makeUserWithPermissions(['cash.read']);
        $historyUser = $this->makeUserWithPermissions(['cash.read', 'cash.filter_all_dates']);

        // Usuario sin permiso histórico: imprimir corte de hace 5 días devuelve 403
        $this->actingAs($restrictedUser)
            ->get(route('cash.printCut', ['cut_id' => $oldCut->id]))
            ->assertStatus(403);

        // Usuario sin permiso histórico: pedir gastos con period=todos no incluye GASTO_ANTIGUO_FUERA_DE_VENTANA
        $respRestricted = $this->actingAs($restrictedUser)
            ->get(route('cash.expenses', ['period' => 'todos']));
        $respRestricted->assertStatus(200);
        $respRestricted->assertDontSee('GASTO_ANTIGUO_FUERA_DE_VENTANA');
        $respRestricted->assertSee('GASTO_RECIENTE_DENTRO_DE_VENTANA');

        // Usuario CON permiso histórico: sí puede ver gastos de todos los registros e imprimir corte antiguo
        $this->actingAs($historyUser)
            ->get(route('cash.printCut', ['cut_id' => $oldCut->id]))
            ->assertStatus(200);

        $respHistory = $this->actingAs($historyUser)
            ->get(route('cash.expenses', ['period' => 'todos']));
        $respHistory->assertStatus(200);
        $respHistory->assertSee('GASTO_ANTIGUO_FUERA_DE_VENTANA');
    }
}

