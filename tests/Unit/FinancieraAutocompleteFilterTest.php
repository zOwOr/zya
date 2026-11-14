<?php

namespace Tests\Unit;

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\FinSaleController;
use App\Http\Controllers\Dashboard\FinancierasController;
use App\Models\User;
use App\Services\FinancieraFilterService;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\MySqlConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class FinancieraAutocompleteFilterTest extends TestCase
{
    /**
     * Build an in-memory user stub with explicit permission flags for offline view/controller rendering.
     */
    protected function makeFakeUser(bool $canMulti): User
    {
        $user = new class extends User {
            public bool $allowMulti = false;

            public function isSuperAdmin(): bool
            {
                return false;
            }

            public function hasPermissionTo($permission, $guardName = null): bool
            {
                $name = is_string($permission) ? $permission : ($permission->name ?? '');

                return $this->can($name);
            }

            public function can($abilities, $arguments = []): bool
            {
                if ($abilities === FinancieraFilterService::PERMISSION_FILTER_MULTIPLE) {
                    return $this->allowMulti;
                }

                return in_array($abilities, [
                    'dashboard.menu',
                    'dashboard.read',
                    'dashboard.financieras.export_pdf',
                    'dashboard.financieras.export_excel',
                    'dashboard.filter_all_dates',
                    'dashboard.all_branches',
                    'financieras.menu',
                    'financieras.read',
                    'financieras.ventas.menu',
                    'financieras.ventas.read',
                    'financieras.ventas.export_pdf',
                    'financieras.ventas.export_excel',
                    'financieras.filter_all_dates',
                    'financieras.inventario.all_branches',
                ], true);
            }
        };

        $user->id = 999;
        $user->name = 'Tester';
        $user->branch_id = 1;
        $user->allowMulti = $canMulti;

        return $user;
    }

    /**
     * Execute a callback under a mocked MySqlConnection that captures SQL queries without hitting MySQL.
     *
     * @param  callable(array<int, string>): void  $callback
     */
    protected function withMockedDatabase(callable $callback): void
    {
        $capturedQueries = [];

        $fakeConn = new class(fn () => null, 'zya', '', [], $capturedQueries) extends MySqlConnection {
            private array $logRef;

            public function __construct($pdo, $database, $tablePrefix, $config, array &$logRef)
            {
                parent::__construct($pdo, $database, $tablePrefix, $config);
                $this->logRef = &$logRef;
            }

            public function select($query, $bindings = [], $useReadPdo = true)
            {
                $this->logRef[] = $query;

                if (stripos($query, 'fin_financieras') !== false) {
                    return [
                        (object) ['id' => 1, 'name' => 'CREDIJUSTO', 'is_active' => 1],
                        (object) ['id' => 2, 'name' => 'CREDIMÁS', 'is_active' => 1],
                        (object) ['id' => 3, 'name' => 'KREDIYA', 'is_active' => 1],
                        (object) ['id' => 4, 'name' => 'MACROPAY', 'is_active' => 1],
                        (object) ['id' => 5, 'name' => 'ND', 'is_active' => 1],
                        (object) ['id' => 6, 'name' => 'PAYJOY', 'is_active' => 1],
                    ];
                }

                if (stripos($query, 'branches') !== false) {
                    return [
                        (object) ['id' => 1, 'name' => 'Allende'],
                    ];
                }

                return [];
            }

            public function selectOne($query, $bindings = [], $useReadPdo = true)
            {
                $this->logRef[] = $query;

                return null;
            }

            public function cursor($query, $bindings = [], $useReadPdo = true)
            {
                $this->logRef[] = $query;
                if (false) {
                    yield null;
                }
            }

            public function statement($query, $bindings = [])
            {
                $this->logRef[] = $query;

                return true;
            }

            public function affectingStatement($query, $bindings = [])
            {
                $this->logRef[] = $query;

                return 0;
            }

            public function getPdo()
            {
                return null;
            }
        };

        $resolver = new class($fakeConn) implements ConnectionResolverInterface {
            public function __construct(private MySqlConnection $conn) {}

            public function connection($name = null)
            {
                return $this->conn;
            }

            public function getDefaultConnection()
            {
                return 'mysql';
            }

            public function setDefaultConnection($name) {}
        };

        $originalModelResolver = Model::getConnectionResolver();
        $originalDb = app()->bound('db') ? app()->make('db') : null;

        try {
            Model::setConnectionResolver($resolver);
            app()->instance('db', $resolver);

            $callback($capturedQueries);
        } finally {
            Model::setConnectionResolver($originalModelResolver);
            if ($originalDb !== null) {
                app()->instance('db', $originalDb);
            }
            Auth::logout();
        }
    }

    public function test_dashboard_abonos_renders_select2_autocomplete_for_multi_and_single_modes(): void
    {
        $this->withMockedDatabase(function () {
            $controller = app()->make(DashboardController::class);

            // Modo Múltiple (con permiso financieras.filter_multiple)
            Auth::setUser($this->makeFakeUser(true));
            $reqMulti = Request::create('/dashboard', 'GET', ['financiera_id' => ['2', '6']]);
            app()->instance('request', $reqMulti);
            $htmlMulti = $controller->index($reqMulti)->render();

            $this->assertStringContainsString('id="dashFinancieraSelect"', $htmlMulti);
            $this->assertStringContainsString('name="financiera_id[]"', $htmlMulti);
            $this->assertStringContainsString('multiple="multiple"', $htmlMulti);
            $this->assertStringContainsString('select2-autocomplete', $htmlMulti);
            $this->assertStringContainsString('data-allow-clear="true"', $htmlMulti);
            $this->assertStringContainsString('data-clear-select="#dashFinancieraSelect"', $htmlMulti);
            $this->assertStringContainsString('>CREDIMÁS</option>', $htmlMulti);
            $this->assertMatchesRegularExpression('/value="2"\s+selected>CREDIMÁS<\/option>/', $htmlMulti);
            $this->assertMatchesRegularExpression('/value="6"\s+selected>PAYJOY<\/option>/', $htmlMulti);

            // Modo Individual (sin permiso financieras.filter_multiple)
            Auth::setUser($this->makeFakeUser(false));
            $reqSingle = Request::create('/dashboard', 'GET', ['financiera_id' => '2']);
            app()->instance('request', $reqSingle);
            $htmlSingle = $controller->index($reqSingle)->render();

            $this->assertStringContainsString('id="dashFinancieraSelect"', $htmlSingle);
            $this->assertStringContainsString('name="financiera_id"', $htmlSingle);
            $this->assertStringNotContainsString('name="financiera_id[]"', $htmlSingle);
            $this->assertStringNotContainsString('multiple="multiple"', $htmlSingle);
            $this->assertStringContainsString('select2-autocomplete', $htmlSingle);
            $this->assertStringContainsString('data-allow-clear="true"', $htmlSingle);
            $this->assertStringContainsString('data-clear-select="#dashFinancieraSelect"', $htmlSingle);
            $this->assertMatchesRegularExpression('/value="2"\s+selected>CREDIMÁS<\/option>/', $htmlSingle);
        });
    }

    public function test_ventas_financieras_renders_select2_autocomplete_for_multi_and_single_modes(): void
    {
        $this->withMockedDatabase(function () {
            $controller = app()->make(FinancierasController::class);

            // Modo Múltiple
            Auth::setUser($this->makeFakeUser(true));
            $reqMulti = Request::create('/financieras', 'GET', [
                'tab' => 'ventas',
                'financiera_id' => ['2', '6'],
            ]);
            app()->instance('request', $reqMulti);
            $htmlMulti = $controller->index($reqMulti)->render();

            $this->assertStringContainsString('id="ventasFinancieraSelect"', $htmlMulti);
            $this->assertStringContainsString('name="financiera_id[]"', $htmlMulti);
            $this->assertStringContainsString('multiple="multiple"', $htmlMulti);
            $this->assertStringContainsString('select2-autocomplete', $htmlMulti);
            $this->assertStringContainsString('data-allow-clear="true"', $htmlMulti);
            $this->assertStringContainsString('data-clear-select="#ventasFinancieraSelect"', $htmlMulti);
            $this->assertMatchesRegularExpression('/value="2"\s+selected>CREDIMÁS<\/option>/', $htmlMulti);
            $this->assertMatchesRegularExpression('/value="6"\s+selected>PAYJOY<\/option>/', $htmlMulti);

            // Modo Individual
            Auth::setUser($this->makeFakeUser(false));
            $reqSingle = Request::create('/financieras', 'GET', [
                'tab' => 'ventas',
                'financiera_id' => '2',
            ]);
            app()->instance('request', $reqSingle);
            $htmlSingle = $controller->index($reqSingle)->render();

            $this->assertStringContainsString('id="ventasFinancieraSelect"', $htmlSingle);
            $this->assertStringContainsString('name="financiera_id"', $htmlSingle);
            $this->assertStringNotContainsString('name="financiera_id[]"', $htmlSingle);
            $this->assertStringNotContainsString('multiple="multiple"', $htmlSingle);
            $this->assertStringContainsString('select2-autocomplete', $htmlSingle);
            $this->assertStringContainsString('data-allow-clear="true"', $htmlSingle);
            $this->assertStringContainsString('data-clear-select="#ventasFinancieraSelect"', $htmlSingle);
        });
    }

    public function test_fin_sale_controller_orders_financieras_alphabetically(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Dashboard/FinSaleController.php'));

        $this->assertNotFalse($source);
        $this->assertGreaterThanOrEqual(
            2,
            preg_match_all('/FinFinanciera::where\([\'"`]?is_active[\'"`]?,\s*true\)\s*->orderBy\([\'"`]?name[\'"`]?\)\s*->get\(\)/', $source),
            'FinSaleController::index() and create() should both sort active financieras alphabetically by name.'
        );
    }

    public function test_main_layout_contains_accent_insensitive_select2_handlers_and_clean_css(): void
    {
        $mainBlade = file_get_contents(resource_path('views/dashboard/body/main.blade.php'));
        $filterStyles = file_get_contents(resource_path('views/financieras/partials/filter-styles.blade.php'));

        $this->assertNotFalse($mainBlade);
        $this->assertNotFalse($filterStyles);

        // Sin bloque CSS huérfano antiguo
        $this->assertDoesNotMatchRegularExpression(
            '/\}\s*\n\s*border-radius:\s*\.25rem\s*!important;/',
            $mainBlade
        );

        // Matcher insensible a acentos y mayúsculas + autofocus + limpieza inline tras selección múltiple
        $this->assertStringContainsString('window.normalizeSelect2SearchText', $mainBlade);
        $this->assertStringContainsString('window.accentInsensitiveSelect2Matcher', $mainBlade);
        $this->assertStringContainsString('minimumResultsForSearch: 0', $mainBlade);
        $this->assertStringContainsString('closeOnSelect: isMultiple ? false : true', $mainBlade);
        $this->assertStringContainsString("$(document).on('select2:open'", $mainBlade);
        $this->assertStringContainsString('select2:select select2:unselect', $mainBlade);
        $this->assertStringContainsString("inlineField.value = ''", $mainBlade);
        $this->assertStringContainsString("$(document).on('click', '[data-clear-select]'", $mainBlade);

        // Regla de compresión visual del input inline en ambos stylesheets
        $this->assertStringContainsString('.select2-selection__rendered:not(:empty) ~ .select2-search--inline', $mainBlade);
        $this->assertStringContainsString('.select2-selection__rendered:not(:empty) ~ .select2-search--inline', $filterStyles);
    }
}

