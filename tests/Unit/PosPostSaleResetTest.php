<?php

namespace Tests\Unit;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PosPostSaleResetTest extends TestCase
{
    private function makeOfflineMySqlConnection(): MySqlConnection
    {
        return new class extends MySqlConnection {
            public function __construct()
            {
            }

            public function getName()
            {
                return 'mysql';
            }

            public function select($query, $bindings = [], $useReadPdo = true)
            {
                return [];
            }

            public function selectOne($query, $bindings = [], $useReadPdo = true)
            {
                return null;
            }

            public function insert($query, $bindings = [], $sequence = null)
            {
                return true;
            }

            public function update($query, $bindings = [])
            {
                return 0;
            }

            public function delete($query, $bindings = [])
            {
                return 0;
            }

            public function statement($query, $bindings = [])
            {
                return true;
            }

            public function affectingStatement($query, $bindings = [])
            {
                return 0;
            }

            public function table($table, $as = null)
            {
                return new class($this, $this->getQueryGrammar(), $this->getPostProcessor()) extends QueryBuilder {
                    public function get($columns = ['*'])
                    {
                        return collect();
                    }

                    public function count($columns = '*')
                    {
                        return 0;
                    }

                    public function value($column)
                    {
                        return null;
                    }

                    public function exists()
                    {
                        return false;
                    }
                };
            }

            public function getQueryGrammar()
            {
                return new MySqlGrammar();
            }

            public function getPostProcessor()
            {
                return new MySqlProcessor();
            }

            public function getSchemaBuilder()
            {
                return new MySqlBuilder($this);
            }

            public function getDatabaseName()
            {
                return 'fake';
            }

            public function getTablePrefix()
            {
                return '';
            }
        };
    }

    private function renderCentralPosView(): string
    {
        $prevResolver = Model::getConnectionResolver();
        $prevDb = $this->app->bound('db') ? $this->app->make('db') : null;
        $prevDbConn = $this->app->bound('db.connection') ? $this->app->make('db.connection') : null;

        $fakeConn = $this->makeOfflineMySqlConnection();
        $fakeResolver = new class($fakeConn) implements ConnectionResolverInterface {
            private MySqlConnection $conn;

            public function __construct(MySqlConnection $conn)
            {
                $this->conn = $conn;
            }

            public function connection($name = null)
            {
                return $this->conn;
            }

            public function getDefaultConnection()
            {
                return 'mysql';
            }

            public function setDefaultConnection($name)
            {
            }
        };

        try {
            Model::setConnectionResolver($fakeResolver);
            $this->app->instance('db', $fakeResolver);
            $this->app->instance('db.connection', $fakeConn);
            Gate::before(fn () => true);

            $user = new class extends User {
                public function isSuperAdmin(): bool
                {
                    return true;
                }

                public function can($abilities, $arguments = []): bool
                {
                    return true;
                }

                public function hasPermissionTo($permission, $guardName = null): bool
                {
                    return true;
                }

                public function getKey()
                {
                    return 77;
                }

                public function getAuthIdentifier()
                {
                    return 77;
                }
            };
            $user->id = 77;
            $user->name = 'Cajero Test';
            $user->email = 'cajero@test.local';
            $user->branch_id = 1;
            $user->setRelation('branch', (object) ['id' => 1, 'name' => 'Allende']);
            Auth::setUser($user);

            $gift = new class {
                public int $id = 9;
                public string $code = 'GFT-9';
                public string $name = 'Termo ZYA';
                public string $brand = 'ZYA';
                public string $supplier_display_name = 'Proveedora';

                public function stockInBranch($branchId): int
                {
                    return 5;
                }
            };

            return view('central-pos.index', [
                'branches' => collect([(object) ['id' => 1, 'name' => 'Allende']]),
                'branchId' => 1,
                'financieras' => collect([(object) ['id' => 1, 'name' => 'PAYJOY']]),
                'customers' => collect([(object) ['id' => 10, 'tit_name' => 'María López', 'tit_phone' => '8261112233']]),
                'externalCustomers' => collect([
                    (object) ['id' => 88, 'tit_name' => 'Externo Demo', 'tit_phone' => '8269998877', 'tit_city' => 'Allende', 'status' => 'ACTIVO'],
                ]),
                'extCustomerStatusOptions' => ['ACTIVO' => 'ACTIVO'],
                'loanTypes' => collect([
                    (object) [
                        'id' => 5,
                        'name' => 'Semanal VIP',
                        'code' => 'VIP',
                        'default_interest_rate' => 10,
                        'default_opening_fee' => 150,
                        'default_frequency' => 'semanal',
                        'default_periods' => 8,
                    ],
                ]),
                'tandas' => collect([
                    (object) [
                        'id' => 11,
                        'description' => 'Tanda Navideña',
                        'payment_amount' => 500.00,
                        'start_date' => Carbon::parse('2026-11-01'),
                        'created_at' => Carbon::parse('2026-11-01'),
                        'participants' => collect([
                            (object) ['id' => 101, 'position' => 1, 'display_name' => 'Juan Pérez', 'display_phone' => '8261001122'],
                        ]),
                    ],
                ]),
                'products' => collect([
                    (object) ['id' => 41, 'product_name' => 'Funda Silicon', 'product_code' => 'FUN-41', 'imei' => '111222333', 'selling_price' => 250, 'stock_quantity' => 3],
                ]),
                'gifts' => collect([$gift]),
                'recentTransactions' => collect([]),
                'activeTab' => 'financieras',
                'canFinancieras' => true,
                'canGifts' => true,
                'canPosExternalCustomers' => true,
                'canPrestamos' => true,
                'canProductos' => true,
                'canSecProductos' => true,
                'canTandas' => true,
            ])->render();
        } finally {
            Auth::logout();
            if ($prevResolver !== null) {
                Model::setConnectionResolver($prevResolver);
            }
            if ($prevDb !== null) {
                $this->app->instance('db', $prevDb);
            }
            if ($prevDbConn !== null) {
                $this->app->instance('db.connection', $prevDbConn);
            }
        }
    }

    public function test_central_pos_compiles_and_renders_full_post_sale_reset_architecture(): void
    {
        $source = file_get_contents(resource_path('views/central-pos/index.blade.php'));
        $this->assertNotEmpty(Blade::compileString($source));

        $html = $this->renderCentralPosView();

        $this->assertStringContainsString('function createDefaultTab(', $html);
        $this->assertStringContainsString('function normalizeTabPaymentDefaults(', $html);
        $this->assertStringContainsString('function resetPaymentDomInputs(', $html);
        $this->assertStringContainsString('function resetTabState(', $html);
        $this->assertStringContainsString('function resetDynamicProductInputs(', $html);
        $this->assertStringContainsString('function resetFinancieraSectionInputs(', $html);
        $this->assertStringContainsString('function resetLoansSectionInputs(', $html);
        $this->assertStringContainsString('function resetTandasSectionInputs(', $html);
        $this->assertStringContainsString('function decrementSoldStockInDom(', $html);
        $this->assertStringContainsString('function resetProductsSectionInputs(', $html);
        $this->assertStringContainsString('function resetModalsAfterSale(', $html);
        $this->assertStringContainsString('function resetPosWorkspaceAfterSale(', $html);
    }

    public function test_empty_cart_payment_inputs_stay_blank_and_gift_zero_total_retains_zero_fallback(): void
    {
        $html = $this->renderCentralPosView();

        $this->assertStringContainsString("const emptyOrZero = tab.items.length > 0 ? '0.00' : '';", $html);
        $this->assertStringContainsString("const defaultAmountFallback = tab.items.length > 0 ? saleTotal.toFixed(2) : '';", $html);
        $this->assertStringContainsString('if (cashInput && (document.activeElement !== cashInput || tab.items.length === 0)) {', $html);
        $this->assertStringContainsString('if (cardInput && (document.activeElement !== cardInput || tab.items.length === 0)) {', $html);
        $this->assertStringContainsString('if (transInput && (document.activeElement !== transInput || tab.items.length === 0)) {', $html);
    }

    public function test_checkout_success_and_clear_buttons_invoke_complete_cleanup_without_touching_shift_hardware_checks(): void
    {
        $html = $this->renderCentralPosView();

        $this->assertStringContainsString('const soldItemsSnapshot = Array.isArray(tab.items) ? tab.items.map(it => Object.assign({}, it)) : [];', $html);
        $this->assertStringContainsString('resetPosWorkspaceAfterSale(tab, soldItemsSnapshot);', $html);
        $this->assertStringContainsString("document.getElementById('btnClearCurrentTab').addEventListener('click', function () {", $html);
        $this->assertStringContainsString('resetTabState(tab);', $html);

        // Request sequence guards prevent late async lookup responses from re-populating stale results
        $this->assertStringContainsString('let pendingNotesRequestSeq = 0;', $html);
        $this->assertStringContainsString('let finLookupRequestSeq = 0;', $html);
        $this->assertStringContainsString('let loanLookupRequestSeq = 0;', $html);
        $this->assertStringContainsString('let tandaLookupRequestSeq = 0;', $html);
        $this->assertStringContainsString('let giftLookupRequestSeq = 0;', $html);

        // Shift hardware toggles (#posOpenDrawerCheck / #posAutoPrintCheck) must only be read, never unchecked in resetPaymentDomInputs
        $resetPaymentStart = strpos($html, 'function resetPaymentDomInputs()');
        $resetPaymentEnd = strpos($html, 'function resetTabState(');
        $this->assertNotFalse($resetPaymentStart);
        $this->assertNotFalse($resetPaymentEnd);
        $resetPaymentBody = substr($html, $resetPaymentStart, $resetPaymentEnd - $resetPaymentStart);
        $this->assertStringNotContainsString('posOpenDrawerCheck', $resetPaymentBody);
        $this->assertStringNotContainsString('posAutoPrintCheck', $resetPaymentBody);
    }
}
