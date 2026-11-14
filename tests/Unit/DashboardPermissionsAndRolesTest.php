<?php

namespace Tests\Unit;

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\RoleController;
use App\Models\Branch;
use App\Models\PosTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DashboardPermissionsAndRolesTest extends TestCase
{
    protected function tearDown(): void
    {
        Auth::logout();
        parent::tearDown();
    }

    private function makeTestUser(
        array $permissions = [],
        bool $superAdmin = false,
        int $branchId = 1
    ): User {
        $user = new class extends User {
            public array $grantedPermissions = [];
            public bool $isSuper = false;

            public function isSuperAdmin(): bool
            {
                return $this->isSuper;
            }

            public function can($abilities, $arguments = []): bool
            {
                if ($this->isSuper) {
                    return true;
                }

                if (is_array($abilities)) {
                    foreach ($abilities as $ability) {
                        if (in_array($ability, $this->grantedPermissions, true)) {
                            return true;
                        }
                    }

                    return false;
                }

                return in_array((string) $abilities, $this->grantedPermissions, true);
            }

            public function canAny($abilities, $arguments = []): bool
            {
                return $this->can((array) $abilities, $arguments);
            }
        };

        $user->id = $superAdmin ? 1 : 99;
        $user->branch_id = $branchId;
        $user->name = 'Test User';
        $user->grantedPermissions = $permissions;
        $user->isSuper = $superAdmin;

        return $user;
    }

    public function test_dashboard_routes_and_controller_permission_mapping_are_enforced(): void
    {
        $routeExpectations = [
            'dashboard' => 'permission:dashboard.menu|dashboard.read',
            'dashboard.financieras.pdf' => 'permission:dashboard.financieras.export_pdf',
            'dashboard.financieras.excel' => 'permission:dashboard.financieras.export_excel',
            'dashboard.ventas.pdf' => 'permission:dashboard.ventas.export_pdf',
            'dashboard.ventas.excel' => 'permission:dashboard.ventas.export_excel',
            'dashboard.transactions.cancel' => 'permission:dashboard.cancel_transaction',
            'rolePermission.store' => 'permission:roles.menu|roles.read|roles.edit|permissions.menu|permissions.read',
            'rolePermission.update' => 'permission:roles.menu|roles.read|roles.edit|permissions.menu|permissions.read',
        ];

        foreach ($routeExpectations as $routeName => $expectedMiddleware) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "La ruta {$routeName} debe existir.");
            $this->assertContains(
                $expectedMiddleware,
                $route->gatherMiddleware(),
                "La ruta {$routeName} debe requerir {$expectedMiddleware}."
            );
        }

        $controller = new DashboardController();
        $permissionForAction = new ReflectionMethod($controller, 'permissionForAction');
        $permissionForAction->setAccessible(true);

        $this->assertSame('dashboard.read', $permissionForAction->invoke($controller, 'index'));
        $this->assertSame('dashboard.financieras.export_pdf', $permissionForAction->invoke($controller, 'exportFinancieraPaymentsPdf'));
        $this->assertSame('dashboard.financieras.export_excel', $permissionForAction->invoke($controller, 'exportFinancieraPaymentsExcel'));
        $this->assertSame('dashboard.ventas.export_pdf', $permissionForAction->invoke($controller, 'exportVentasPdf'));
        $this->assertSame('dashboard.ventas.export_excel', $permissionForAction->invoke($controller, 'exportVentasExcel'));
        $this->assertNull($permissionForAction->invoke($controller, 'cancelTransaction'));

        $roleController = new RoleController();
        $rolePermForAction = new ReflectionMethod($roleController, 'permissionForAction');
        $rolePermForAction->setAccessible(true);
        $this->assertSame('roles.edit', $rolePermForAction->invoke($roleController, 'rolePermissionStore'));
        $this->assertSame('roles.edit', $rolePermForAction->invoke($roleController, 'rolePermissionUpdate'));
    }

    public function test_cancel_transaction_enforces_cancel_permissions_and_branch_isolation(): void
    {
        $controller = new DashboardController();
        $authorize = new ReflectionMethod($controller, 'authorizeTransactionCancellation');
        $authorize->setAccessible(true);

        $sameBranchTx = new PosTransaction(['branch_id' => 1]);
        $otherBranchTx = new PosTransaction(['branch_id' => 2]);

        // Sin permiso de cancelación en Dashboard ni en Cobros -> 403 directo en cancelTransaction
        Auth::setUser($this->makeTestUser(['dashboard.read'], false, 1));
        $caughtUnauthorized = null;
        try {
            $controller->cancelTransaction(Request::create('/dashboard/transactions/1/cancel', 'POST'), $sameBranchTx);
        } catch (HttpException $e) {
            $caughtUnauthorized = $e;
        }
        $this->assertInstanceOf(HttpException::class, $caughtUnauthorized);
        $this->assertSame(403, $caughtUnauthorized->getStatusCode());

        // Con dashboard.cancel_transaction en su propia sucursal -> permitido
        Auth::setUser($this->makeTestUser(['dashboard.cancel_transaction'], false, 1));
        $authorize->invoke($controller, $sameBranchTx);

        // Con dashboard.cancel_transaction pero intentando cancelar otra sucursal -> 403
        $caughtCrossBranch = null;
        try {
            $controller->cancelTransaction(Request::create('/dashboard/transactions/2/cancel', 'POST'), $otherBranchTx);
        } catch (HttpException $e) {
            $caughtCrossBranch = $e;
        }
        $this->assertInstanceOf(HttpException::class, $caughtCrossBranch);
        $this->assertSame(403, $caughtCrossBranch->getStatusCode());

        // Con solo cobros.cancel_transaction (sin dashboard.cancel_transaction) -> 403 en Dashboard
        Auth::setUser($this->makeTestUser(['cobros.cancel_transaction'], false, 1));
        $caughtCobrosOnly = null;
        try {
            $controller->cancelTransaction(Request::create('/dashboard/transactions/1/cancel', 'POST'), $sameBranchTx);
        } catch (HttpException $e) {
            $caughtCobrosOnly = $e;
        }
        $this->assertInstanceOf(HttpException::class, $caughtCobrosOnly);
        $this->assertSame(403, $caughtCobrosOnly->getStatusCode());

        // Con dashboard.cancel_transaction + dashboard.all_branches -> otra sucursal permitida
        Auth::setUser($this->makeTestUser(['dashboard.cancel_transaction', 'dashboard.all_branches'], false, 1));
        $authorize->invoke($controller, $otherBranchTx);

        // SuperAdmin -> siempre permitido aunque no tenga permisos explícitos
        Auth::setUser($this->makeTestUser([], true, 1));
        $authorize->invoke($controller, $otherBranchTx);
        $this->assertTrue(true);
    }

    public function test_dashboard_branch_filter_and_order_kpis_are_branch_scoped(): void
    {
        $controller = new DashboardController();
        $canAll = new ReflectionMethod($controller, 'canAccessAllBranches');
        $canAll->setAccessible(true);
        $resolveBranch = new ReflectionMethod($controller, 'resolveBranchFilter');
        $resolveBranch->setAccessible(true);

        Auth::setUser($this->makeTestUser(['dashboard.read'], false, 3));
        $this->assertFalse($canAll->invoke($controller));
        $this->assertSame(3, $resolveBranch->invoke($controller, Request::create('/dashboard', 'GET', ['branch_id' => 2])));

        Auth::setUser($this->makeTestUser(['dashboard.read', 'dashboard.all_branches'], false, 3));
        $this->assertTrue($canAll->invoke($controller));
        $this->assertSame(2, $resolveBranch->invoke($controller, Request::create('/dashboard', 'GET', ['branch_id' => 2])));
        $this->assertNull($resolveBranch->invoke($controller, Request::create('/dashboard', 'GET')));

        $controllerSource = file_get_contents(app_path('Http/Controllers/Dashboard/DashboardController.php'));
        $this->assertStringContainsString("Schema::hasColumn('orders', 'branch_id')", $controllerSource);
        $this->assertStringContainsString("where('branch_id', \$branchId)", $controllerSource);
    }

    public function test_role_controller_extracts_empty_and_valid_permissions_and_clears_spatie_cache(): void
    {
        $controller = new RoleController();

        $registrar = new class(app('cache')) extends PermissionRegistrar {
            public int $forgetCalls = 0;

            public function forgetCachedPermissions(): bool
            {
                $this->forgetCalls++;

                return true;
            }
        };
        app()->instance(PermissionRegistrar::class, $registrar);

        $forgetMethod = new ReflectionMethod($controller, 'forgetPermissionCache');
        $forgetMethod->setAccessible(true);
        $forgetMethod->invoke($controller);
        $this->assertSame(1, $registrar->forgetCalls);

        $extractMethod = new ReflectionMethod($controller, 'extractPermissionIds');
        $extractMethod->setAccessible(true);

        $nonEmptyRequest = new class extends Request {
            public array $capturedRules = [];

            public function validate(array $rules, ...$params)
            {
                $this->capturedRules = $rules;

                return ['permission_id' => ['14', '3', '14']];
            }
        };
        $ids = $extractMethod->invoke($controller, $nonEmptyRequest);
        $this->assertSame([14, 3], $ids);
        $this->assertSame([
            'permission_id' => 'nullable|array',
            'permission_id.*' => 'integer|exists:permissions,id',
        ], $nonEmptyRequest->capturedRules);

        $emptyRequest = new class extends Request {
            public function validate(array $rules, ...$params)
            {
                return [];
            }
        };
        $this->assertSame([], $extractMethod->invoke($controller, $emptyRequest));

        $roleControllerSource = file_get_contents(app_path('Http/Controllers/Dashboard/RoleController.php'));
        $this->assertStringNotContainsString("DB::table('role_has_permissions')->insert", $roleControllerSource);
        $this->assertStringNotContainsString('if(!empty($permissions))', $roleControllerSource);
        $this->assertGreaterThanOrEqual(2, substr_count($roleControllerSource, '->syncPermissions($this->extractPermissionIds($request))'));
        $this->assertGreaterThanOrEqual(7, substr_count($roleControllerSource, '$this->forgetPermissionCache()'));
    }

    public function test_dashboard_and_role_permission_views_compile_and_sync_group_checkboxes(): void
    {
        $dashboardView = file_get_contents(resource_path('views/dashboard/index.blade.php'));
        $this->assertNotEmpty(Blade::compileString($dashboardView));
        $this->assertStringContainsString("can('dashboard.cancel_transaction')", $dashboardView);
        $this->assertStringNotContainsString("can('cobros.cancel_transaction')", $dashboardView);

        foreach ([
            'views/roles/role-permission-edit.blade.php',
            'views/roles/role-permission-create.blade.php',
        ] as $viewPath) {
            $source = file_get_contents(resource_path($viewPath));
            $this->assertNotEmpty(Blade::compileString($source), "{$viewPath} debe compilar correctamente.");
            $this->assertStringContainsString('permission-group-row', $source);
            $this->assertStringContainsString('permission-group-checkbox', $source);
            $this->assertStringContainsString('permission-item-checkbox', $source);
            $this->assertStringContainsString('syncCheckAll', $source);
            $this->assertStringContainsString('syncGroupBox', $source);
            $this->assertStringNotContainsString('ajax.googleapis.com', $source);
        }
    }

    public function test_dashboard_view_hides_cancel_button_without_dashboard_cancel_permission(): void
    {
        $branch = new Branch(['name' => 'Allende']);
        $branch->id = 1;

        $tx = new PosTransaction([
            'folio' => 'MOV-20261114-8K33N',
            'transaction_code' => 'MOV-20261114-8K33N',
            'transaction_date' => Carbon::parse('2026-11-14 09:59:00'),
            'customer_name' => 'INES FRANCISCO NIÑO CORREA',
            'tab_label' => 'POS Central',
            'branch_id' => 1,
            'items_payload' => [[
                'section' => 'financiera_abono',
                'title' => 'Abono Financiera (PAYJOY)',
                'total' => 688.00,
            ]],
            'payment_method' => 'efectivo',
            'cash_amount' => 688.00,
            'card_amount' => 0,
            'transfer_amount' => 0,
            'total_amount' => 688.00,
            'status' => 'completado',
        ]);
        $tx->id = 777;
        $tx->setRelation('branch', $branch);

        $emptyPaginator = static fn (string $pageName) => new LengthAwarePaginator([], 0, 15, 1, [
            'path' => '/dashboard',
            'pageName' => $pageName,
        ]);
        $movPaginator = new LengthAwarePaginator([$tx], 1, 15, 1, [
            'path' => '/dashboard',
            'pageName' => 'mov_page',
        ]);

        $viewData = [
            'total_paid' => 0,
            'total_due' => 0,
            'complete_orders' => collect(),
            'products' => collect(),
            'new_products' => collect(),
            'financieraPayments' => $emptyPaginator('fin_page'),
            'finTotals' => ['count' => 0, 'amount' => 0, 'commission' => 0, 'total_paid' => 0, 'cash' => 0, 'card' => 0, 'transfer' => 0],
            'finDateFilter' => ['filter_type' => 'dia', 'date' => '2026-11-14'],
            'finFilterType' => 'dia',
            'finDate' => '2026-11-14',
            'finStartDate' => '2026-11-14',
            'finEndDate' => '2026-11-14',
            'finPeriodLabel' => 'Día: 14/11/2026',
            'dailyMovements' => $movPaginator,
            'movTotals' => ['count' => 1, 'total_rows' => 1, 'cash' => 688.00, 'card' => 0, 'transfer' => 0, 'total' => 688.00],
            'movDateFilter' => ['filter_type' => 'dia', 'date' => '2026-11-14'],
            'movFilterType' => 'dia',
            'movDate' => '2026-11-14',
            'movStartDate' => '2026-11-14',
            'movEndDate' => '2026-11-14',
            'movPeriodLabel' => 'Día: 14/11/2026',
            'movStatus' => '',
            'movSearch' => '',
            'canDashboardAllDates' => false,
            'minAllowedDate' => '2026-11-12',
            'dateRestrictionTriggered' => false,
            'financieras' => collect(),
            'canMultiFilterFinancieras' => false,
            'selectedFinancieraIds' => [],
            'branches' => collect([$branch]),
            'branchId' => 1,
            'todayClosedBranchIds' => [],
        ];

        $this->app->instance('request', Request::create('/dashboard', 'GET'));

        // Usuario con lectura + PDF + cobros.cancel_transaction pero SIN dashboard.cancel_transaction
        Auth::setUser($this->makeTestUser([
            'dashboard.menu',
            'dashboard.read',
            'dashboard.ventas.export_pdf',
            'cobros.cancel_transaction',
        ], false, 1));
        $noCancelHtml = view('dashboard.index', $viewData)->render();
        $this->assertStringContainsString('Gestión de Movimientos Diarios / Ventas POS', $noCancelHtml);
        $this->assertStringContainsString('MOV-20261114-8K33N', $noCancelHtml);
        $this->assertStringContainsString('Exportar PDF', $noCancelHtml);
        $this->assertStringNotContainsString('Exportar Excel', $noCancelHtml);
        $this->assertStringNotContainsString('Cancelar movimiento', $noCancelHtml);
        $this->assertStringNotContainsString('la-ban', $noCancelHtml);
        $this->assertStringNotContainsString('/dashboard/transactions/777/cancel', $noCancelHtml);

        // Usuario CON dashboard.cancel_transaction sí ve el botón de cancelar
        Auth::setUser($this->makeTestUser([
            'dashboard.menu',
            'dashboard.read',
            'dashboard.cancel_transaction',
        ], false, 1));
        $canCancelHtml = view('dashboard.index', $viewData)->render();
        $this->assertStringContainsString('MOV-20261114-8K33N', $canCancelHtml);
        $this->assertStringContainsString('Cancelar movimiento', $canCancelHtml);
        $this->assertStringContainsString('la-ban', $canCancelHtml);
        $this->assertStringContainsString('/dashboard/transactions/777/cancel', $canCancelHtml);
    }
}
