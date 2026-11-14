<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\FinFinanciera;
use App\Models\FinSale;
use App\Models\FinSalePayment;
use App\Models\User;
use App\Services\FinancieraFilterService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class FinancieraFilterServiceTest extends TestCase
{
    protected function makeFakeUser(bool $canMulti): User
    {
        $user = new class extends User {
            public bool $allowMulti = false;

            public function isSuperAdmin(): bool
            {
                return false;
            }

            public function can($abilities, $arguments = []): bool
            {
                if ($abilities === FinancieraFilterService::PERMISSION_FILTER_MULTIPLE) {
                    return $this->allowMulti;
                }

                return in_array($abilities, [
                    'dashboard.read',
                    'dashboard.menu',
                    'dashboard.financieras.export_pdf',
                    'dashboard.financieras.export_excel',
                    'financieras.read',
                    'financieras.ventas.read',
                    'financieras.ventas.export_pdf',
                    'financieras.ventas.export_excel',
                ], true);
            }

            public function hasPermissionTo($permission, $guardName = null): bool
            {
                return $this->can(is_string($permission) ? $permission : (string) ($permission->name ?? ''));
            }

            public function checkPermissionTo($permission, $guardName = null): bool
            {
                return $this->hasPermissionTo($permission, $guardName);
            }

            public function canAny($abilities, $arguments = []): bool
            {
                foreach ((array) $abilities as $ability) {
                    if ($this->can($ability, $arguments)) {
                        return true;
                    }
                }

                return false;
            }
        };

        $user->allowMulti = $canMulti;
        $user->name = $canMulti ? 'Esmeralda' : 'Usuario Sucursal';
        $user->branch_id = 1;
        $user->id = $canMulti ? 99 : 100;
        $branch = new Branch(['name' => 'Allende']);
        $branch->id = 1;
        $user->setRelation('branch', $branch);

        return $user;
    }

    public function test_normalize_ids_handles_scalars_arrays_duplicates_and_invalid_values(): void
    {
        $this->assertSame([], FinancieraFilterService::normalizeIds(null));
        $this->assertSame([], FinancieraFilterService::normalizeIds(''));
        $this->assertSame([], FinancieraFilterService::normalizeIds([]));
        $this->assertSame([], FinancieraFilterService::normalizeIds(['', '0', '-4', 'abc']));
        $this->assertSame([3], FinancieraFilterService::normalizeIds('3'));
        $this->assertSame([1, 4, 2], FinancieraFilterService::normalizeIds(['1', 4, '1', '2', 'invalid', 0]));
        $this->assertSame([2, 5], FinancieraFilterService::normalizeIds([['2', '5'], '2']));
    }

    public function test_resolve_selected_ids_enforces_single_selection_when_user_lacks_permission(): void
    {
        $esmeralda = $this->makeFakeUser(true);
        $normalUser = $this->makeFakeUser(false);

        $this->assertSame(
            [1, 3, 5],
            FinancieraFilterService::resolveSelectedIds(['1', '3', '5'], $esmeralda)
        );

        // Usuario sin permiso exclusivo: incluso manipulando array en URL se restringe a un solo ID
        $this->assertSame(
            [1],
            FinancieraFilterService::resolveSelectedIds(['1', '3', '5'], $normalUser)
        );

        // Usuario null (sin sesión): falla cerrado en selección única
        $this->assertSame(
            [2],
            FinancieraFilterService::resolveSelectedIds(['2', '4'], null)
        );
    }

    public function test_apply_to_query_generates_where_in_for_authorized_user_and_where_for_restricted_user(): void
    {
        $esmeralda = $this->makeFakeUser(true);
        $normalUser = $this->makeFakeUser(false);

        $multiPaymentsQuery = FinSalePayment::query();
        $appliedMulti = FinancieraFilterService::applyToQuery($multiPaymentsQuery, [10, 20], 'financiera_id', $esmeralda);

        $this->assertSame([10, 20], $appliedMulti);
        $this->assertStringContainsString('in (?, ?)', strtolower($multiPaymentsQuery->toSql()));
        $this->assertSame([10, 20], $multiPaymentsQuery->getBindings());

        $singleSalesQuery = FinSale::query();
        $appliedSingle = FinancieraFilterService::applyToQuery($singleSalesQuery, [10, 20], 'financiera_id', $normalUser);

        $this->assertSame([10], $appliedSingle);
        $this->assertStringNotContainsString(' in (', strtolower($singleSalesQuery->toSql()));
        $this->assertStringContainsString('= ?', strtolower($singleSalesQuery->toSql()));
        $this->assertSame([10], $singleSalesQuery->getBindings());
    }

    public function test_raw_input_from_request_reads_financiera_id_and_fallback_financiera_ids(): void
    {
        $reqPrimary = Request::create('/dashboard', 'GET', ['financiera_id' => ['1', '2']]);
        $this->assertSame(['1', '2'], FinancieraFilterService::rawInputFromRequest($reqPrimary));

        $reqFallback = Request::create('/dashboard', 'GET', ['financiera_ids' => ['4', '6']]);
        $this->assertSame(['4', '6'], FinancieraFilterService::rawInputFromRequest($reqFallback));
    }

    public function test_pdf_views_render_cleanly_with_financiera_filter_label(): void
    {
        Auth::setUser($this->makeFakeUser(true));

        $paymentsPdf = view('dashboard.financiera-payments-pdf', [
            'payments' => collect(),
            'groupedPayments' => collect(),
            'totals' => ['count' => 0, 'amount' => 0, 'commission' => 0, 'total_paid' => 0],
            'finFilterType' => 'dia',
            'finStartDate' => '2026-11-13',
            'finEndDate' => '2026-11-13',
            'finPeriodLabel' => 'Día: 13/11/2026',
            'branchName' => 'Todas las Sucursales',
            'financieraFilterLabel' => 'Credijusto, PayJoy',
        ])->render();

        $this->assertStringContainsString('Credijusto, PayJoy', $paymentsPdf);

        $salesPdf = view('financieras.ventas.list-pdf', [
            'sales' => collect(),
            'totals' => ['count' => 0, 'active_count' => 0, 'price_total' => 0, 'down_payment_total' => 0, 'credit_total' => 0, 'abonos_total' => 0],
            'periodLabel' => 'Mes actual',
            'branchName' => 'Todas las Sucursales',
            'financieraFilterLabel' => 'Credijusto, PayJoy',
        ])->render();

        $this->assertStringContainsString('Credijusto, PayJoy', $salesPdf);
    }

    public function test_blade_templates_render_multiselect_only_with_permission_and_never_crash_on_array_query(): void
    {
        $financieras = collect([
            new FinFinanciera(['name' => 'PayJoy']),
            new FinFinanciera(['name' => 'Credijusto']),
        ]);
        $financieras[0]->id = 1;
        $financieras[1]->id = 2;

        $branches = collect([new Branch(['name' => 'Allende'])]);
        $branches[0]->id = 1;

        $emptyPaginator = function (string $pageName) {
            return (new LengthAwarePaginator([], 0, 15, 1, [
                'path' => '/test',
                'pageName' => $pageName,
            ]))->withQueryString();
        };

        // Registrar Gate en memoria para evaluar @can en vistas sin conexión MySQL activa
        Gate::before(function ($user, $ability) {
            return method_exists($user, 'can') ? $user->can($ability) : false;
        });

        // Simular query string con array ?financiera_id[]=1&financiera_id[]=2
        $request = Request::create('/dashboard', 'GET', ['financiera_id' => ['1', '2']]);
        $this->app->instance('request', $request);

        // 1. Usuario SIN permiso: NO debe ver multiple="multiple" ni fallar por Array to string conversion
        Auth::setUser($this->makeFakeUser(false));
        $restrictedDashboardHtml = view('dashboard.index', [
            'total_paid' => 0,
            'total_due' => 0,
            'complete_orders' => collect(),
            'products' => collect(),
            'new_products' => collect(),
            'financieraPayments' => $emptyPaginator('fin_page'),
            'finTotals' => ['count' => 0, 'amount' => 0, 'commission' => 0, 'total_paid' => 0, 'cash' => 0, 'card' => 0, 'transfer' => 0],
            'finDateFilter' => ['filter_type' => 'dia', 'date' => '2026-11-13'],
            'finFilterType' => 'dia',
            'finDate' => '2026-11-13',
            'finStartDate' => '2026-11-13',
            'finEndDate' => '2026-11-13',
            'finPeriodLabel' => 'Día: 13/11/2026',
            'dailyMovements' => $emptyPaginator('mov_page'),
            'movTotals' => ['count' => 0, 'cash' => 0, 'card' => 0, 'transfer' => 0, 'commission' => 0, 'total' => 0],
            'movDateFilter' => ['filter_type' => 'dia', 'date' => '2026-11-13'],
            'movFilterType' => 'dia',
            'movDate' => '2026-11-13',
            'movStartDate' => '2026-11-13',
            'movEndDate' => '2026-11-13',
            'movPeriodLabel' => 'Día: 13/11/2026',
            'movStatus' => '',
            'movSearch' => '',
            'canDashboardAllDates' => false,
            'minAllowedDate' => '2026-11-11',
            'dateRestrictionTriggered' => false,
            'financieras' => $financieras,
            'canMultiFilterFinancieras' => false,
            'selectedFinancieraIds' => [1],
            'branches' => $branches,
            'branchId' => 1,
            'todayClosedBranchIds' => [],
        ])->render();

        $this->assertStringContainsString('name="financiera_id"', $restrictedDashboardHtml);
        $this->assertStringNotContainsString('name="financiera_id[]"', $restrictedDashboardHtml);

        $restrictedVentasHtml = view('financieras.ventas.index', [
            'sales' => $emptyPaginator('page'),
            'branches' => $branches,
            'financieras' => $financieras,
            'sellers' => collect(),
            'salesDateFilter' => ['was_restricted' => false, 'filter_type' => 'rango', 'date' => '2026-11-13', 'start_date' => '2026-11-01', 'end_date' => '2026-11-13', 'can_all_dates' => false, 'min_allowed_date' => '2026-11-11'],
            'canMultiFilterFinancieras' => false,
            'selectedFinancieraIds' => [1],
        ])->render();

        $this->assertStringContainsString('name="financiera_id"', $restrictedVentasHtml);
        $this->assertStringNotContainsString('name="financiera_id[]"', $restrictedVentasHtml);

        // 2. Usuario CON permiso (Esmeralda): debe ver select múltiple y conservar hidden array en #dashMovFilterForm
        Auth::setUser($this->makeFakeUser(true));
        $esmeDashboardHtml = view('dashboard.index', [
            'total_paid' => 0,
            'total_due' => 0,
            'complete_orders' => collect(),
            'products' => collect(),
            'new_products' => collect(),
            'financieraPayments' => $emptyPaginator('fin_page'),
            'finTotals' => ['count' => 0, 'amount' => 0, 'commission' => 0, 'total_paid' => 0, 'cash' => 0, 'card' => 0, 'transfer' => 0],
            'finDateFilter' => ['filter_type' => 'dia', 'date' => '2026-11-13'],
            'finFilterType' => 'dia',
            'finDate' => '2026-11-13',
            'finStartDate' => '2026-11-13',
            'finEndDate' => '2026-11-13',
            'finPeriodLabel' => 'Día: 13/11/2026',
            'dailyMovements' => $emptyPaginator('mov_page'),
            'movTotals' => ['count' => 0, 'cash' => 0, 'card' => 0, 'transfer' => 0, 'commission' => 0, 'total' => 0],
            'movDateFilter' => ['filter_type' => 'dia', 'date' => '2026-11-13'],
            'movFilterType' => 'dia',
            'movDate' => '2026-11-13',
            'movStartDate' => '2026-11-13',
            'movEndDate' => '2026-11-13',
            'movPeriodLabel' => 'Día: 13/11/2026',
            'movStatus' => '',
            'movSearch' => '',
            'canDashboardAllDates' => false,
            'minAllowedDate' => '2026-11-11',
            'dateRestrictionTriggered' => false,
            'financieras' => $financieras,
            'canMultiFilterFinancieras' => true,
            'selectedFinancieraIds' => [1, 2],
            'branches' => $branches,
            'branchId' => 1,
            'todayClosedBranchIds' => [],
        ])->render();

        $this->assertStringContainsString('name="financiera_id[]"', $esmeDashboardHtml);
        $this->assertStringContainsString('multiple="multiple"', $esmeDashboardHtml);
        $this->assertStringContainsString('<input type="hidden" name="financiera_id[]" value="1">', $esmeDashboardHtml);
        $this->assertStringContainsString('<input type="hidden" name="financiera_id[]" value="2">', $esmeDashboardHtml);

        $esmeVentasHtml = view('financieras.ventas.index', [
            'sales' => $emptyPaginator('page'),
            'branches' => $branches,
            'financieras' => $financieras,
            'sellers' => collect(),
            'salesDateFilter' => ['was_restricted' => false, 'filter_type' => 'rango', 'date' => '2026-11-13', 'start_date' => '2026-11-01', 'end_date' => '2026-11-13', 'can_all_dates' => false, 'min_allowed_date' => '2026-11-11'],
            'canMultiFilterFinancieras' => true,
            'selectedFinancieraIds' => [1, 2],
        ])->render();

        $this->assertStringContainsString('name="financiera_id[]"', $esmeVentasHtml);
        $this->assertStringContainsString('multiple="multiple"', $esmeVentasHtml);
    }
}
