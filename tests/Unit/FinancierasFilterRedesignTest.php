<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\FinBrand;
use App\Models\FinFinanciera;
use App\Models\FinSupplier;
use App\Models\FinWarrantyStage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class FinancierasFilterRedesignTest extends TestCase
{
    private function makeUser(bool $allBranches, bool $multiFin): User
    {
        return new class($allBranches, $multiFin) extends User {
            public int $id = 99;
            public int $branch_id = 1;
            private bool $allBranches;
            private bool $multiFin;

            public function __construct(bool $allBranches = false, bool $multiFin = false)
            {
                parent::__construct();
                $this->allBranches = $allBranches;
                $this->multiFin = $multiFin;
                $this->setRelation('branch', new Branch(['id' => 1, 'name' => 'Allende']));
            }

            public function isSuperAdmin(): bool
            {
                return false;
            }

            public function can($abilities, $arguments = []): bool
            {
                if ($abilities === 'financieras.inventario.all_branches') {
                    return $this->allBranches;
                }
                if ($abilities === 'financieras.ventas.filter_multiple_financieras') {
                    return $this->multiFin;
                }

                return in_array($abilities, [
                    'financieras.ventas.create',
                    'financieras.inventario.create',
                    'financieras.garantias.create',
                    'financieras.robos.create',
                ], true);
            }
        };
    }

    private function emptyPaginator(string $pageName = 'page'): LengthAwarePaginator
    {
        return (new LengthAwarePaginator([], 0, 15, 1, [
            'path' => '/financieras',
            'pageName' => $pageName,
        ]))->withQueryString();
    }

    public function test_all_four_financieras_filter_sections_render_cleanly_and_preserve_every_filter_control(): void
    {
        Gate::before(fn ($user, $ability) => method_exists($user, 'can') ? $user->can($ability) : false);
        Auth::setUser($this->makeUser(true, true));

        $branches = collect([tap(new Branch(['name' => 'Allende']), fn ($b) => $b->id = 1)]);
        $financieras = collect([tap(new FinFinanciera(['name' => 'PayJoy', 'is_active' => true]), fn ($f) => $f->id = 1)]);
        $brands = collect([tap(new FinBrand(['name' => 'Samsung']), fn ($b) => $b->id = 1)]);
        $suppliers = collect([tap(new FinSupplier(['name' => 'Proveedor A']), fn ($s) => $s->id = 1)]);
        $stages = collect([tap(new FinWarrantyStage(['name' => 'Diagnóstico']), fn ($st) => $st->id = 1)]);
        $sellers = collect([tap(new User(['name' => 'Vendedor 1']), fn ($u) => $u->id = 5)]);
        $models = collect(['Galaxy A15']);

        $currentStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $currentEnd = Carbon::now()->endOfMonth()->format('Y-m-d');

        // 1. Ventas y Créditos
        $ventasRequest = Request::create('/financieras', 'GET', [
            'tab' => 'ventas',
            'filter_type' => 'rango',
            'start_date' => $currentStart,
            'end_date' => $currentEnd,
        ]);
        $this->app->instance('request', $ventasRequest);

        $ventasHtml = view('financieras.ventas.index', [
            'sales' => $this->emptyPaginator(),
            'branches' => $branches,
            'financieras' => $financieras,
            'sellers' => $sellers,
            'salesDateFilter' => [
                'filter_type' => 'rango',
                'date' => Carbon::today()->format('Y-m-d'),
                'start_date' => $currentStart,
                'end_date' => $currentEnd,
            ],
            'canMultiFilterFinancieras' => true,
            'selectedFinancieraIds' => [1],
        ])->render();

        $this->assertStringContainsString('id="filter-sales-form"', $ventasHtml);
        $this->assertStringContainsString('fin-filter-shell', $ventasHtml);
        $this->assertStringContainsString('fin-filter-subcard', $ventasHtml);
        $this->assertStringContainsString('Periodo de búsqueda', $ventasHtml);
        $this->assertStringContainsString('fin-period-btn is-active', $ventasHtml);
        $this->assertStringContainsString("onclick=\"setDateRange('current_month')\"", $ventasHtml);
        $this->assertStringContainsString("onclick=\"setDateRange('prev_month')\"", $ventasHtml);
        $this->assertStringContainsString("onclick=\"setDateRange('all')\"", $ventasHtml);
        $this->assertStringContainsString('name="search"', $ventasHtml);
        $this->assertStringContainsString('name="sale_type"', $ventasHtml);
        $this->assertStringContainsString('name="financiera_id[]"', $ventasHtml);
        $this->assertStringContainsString('id="ventasFinancieraSelect"', $ventasHtml);
        $this->assertStringContainsString('name="branch_id"', $ventasHtml);
        $this->assertStringContainsString('name="status"', $ventasHtml);
        $this->assertStringContainsString('name="seller_id"', $ventasHtml);
        $this->assertStringContainsString('id="filter_sale_type"', $ventasHtml);
        $this->assertStringContainsString('id="filter_sale_date"', $ventasHtml);
        $this->assertStringContainsString('id="filter_sale_start_date"', $ventasHtml);
        $this->assertStringContainsString('id="filter_sale_end_date"', $ventasHtml);

        // 2. Inventario
        $invRequest = Request::create('/financieras', 'GET', [
            'tab' => 'inventario',
            'status' => 'vendido',
            'tag' => 'DHTJRXB',
        ]);
        $this->app->instance('request', $invRequest);

        $invHtml = view('financieras.inventario.index', [
            'devices' => $this->emptyPaginator(),
            'branches' => $branches,
            'brands' => $brands,
            'suppliers' => $suppliers,
            'models' => $models,
        ])->render();

        $this->assertStringContainsString('id="filter-inventory-form"', $invHtml);
        $this->assertStringContainsString('fin-filter-shell', $invHtml);
        $this->assertStringContainsString('fin-filter-subcard', $invHtml);
        $this->assertStringContainsString('name="search"', $invHtml);
        $this->assertStringContainsString('name="branch_id"', $invHtml);
        $this->assertStringContainsString('id="statusFilterSelect"', $invHtml);
        $this->assertStringContainsString('id="container-filter-tag"', $invHtml);
        $this->assertStringContainsString('id="input-filter-tag"', $invHtml);
        $this->assertStringContainsString('name="brand_id"', $invHtml);
        $this->assertStringContainsString('name="model"', $invHtml);
        $this->assertStringContainsString('id="modelsDatalist"', $invHtml);
        $this->assertStringContainsString('name="supplier_id"', $invHtml);
        $this->assertStringContainsString('Filtros aplicados (2):', $invHtml);

        // 3. Garantías
        $garRequest = Request::create('/financieras', 'GET', [
            'tab' => 'garantias',
            'status' => 'en_proceso',
        ]);
        $this->app->instance('request', $garRequest);

        $garHtml = view('financieras.garantias.index', [
            'warranties' => $this->emptyPaginator(),
            'branches' => $branches,
            'stages' => $stages,
        ])->render();

        $this->assertStringContainsString('id="filter-warranties-form"', $garHtml);
        $this->assertStringContainsString('fin-filter-shell', $garHtml);
        $this->assertStringContainsString('fin-filter-subcard', $garHtml);
        $this->assertStringContainsString('name="search"', $garHtml);
        $this->assertStringContainsString('name="stage_id"', $garHtml);
        $this->assertStringContainsString('name="branch_id"', $garHtml);
        $this->assertStringContainsString('name="status"', $garHtml);
        $this->assertStringContainsString('En proceso', $garHtml);

        // 4. Robos
        $robosRequest = Request::create('/financieras', 'GET', [
            'tab' => 'robos',
            'search' => '352953',
        ]);
        $this->app->instance('request', $robosRequest);

        $robosHtml = view('financieras.robos.index', [
            'theftReports' => $this->emptyPaginator(),
            'branches' => $branches,
        ])->render();

        $this->assertStringContainsString('id="filter-thefts-form"', $robosHtml);
        $this->assertStringContainsString('fin-filter-shell', $robosHtml);
        $this->assertStringContainsString('fin-filter-subcard', $robosHtml);
        $this->assertStringContainsString('name="search"', $robosHtml);
        $this->assertStringContainsString('name="branch_id"', $robosHtml);
        $this->assertStringContainsString('name="status"', $robosHtml);
        $this->assertStringContainsString('352953', $robosHtml);

        // Verificar que usuario restringido de sucursal renderiza campo readonly en las 4 secciones
        Auth::setUser($this->makeUser(false, false));
        $restrictedRobos = view('financieras.robos.index', [
            'theftReports' => $this->emptyPaginator(),
            'branches' => $branches,
        ])->render();
        $this->assertStringContainsString('readonly title="Tu sucursal asignada"', $restrictedRobos);
    }
}
