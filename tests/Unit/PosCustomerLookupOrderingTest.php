<?php

namespace Tests\Unit;

use App\Http\Controllers\Dashboard\LoanController;
use App\Http\Controllers\Dashboard\UnifiedPosController;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\ExternalCustomer;
use App\Models\FinDevice;
use App\Models\FinFinanciera;
use App\Models\FinSale;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Order;
use App\Models\Tanda;
use App\Models\TandaClient;
use App\Models\User;
use App\Services\DateFilterService;
use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\MySqlConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use ReflectionMethod;
use Tests\TestCase;

class PosCustomerLookupOrderingTest extends TestCase
{
    private function makePosUser(bool $allBranches = true): User
    {
        return new class($allBranches) extends User {
            public int $id = 10;
            public int $branch_id = 1;
            public string $name = 'Cajero POS';
            private bool $allBranches;

            public function __construct(bool $allBranches = true)
            {
                parent::__construct();
                $this->allBranches = $allBranches;
                $this->setRelation('branch', new Branch(['id' => 1, 'name' => 'Allende']));
            }

            public function isSuperAdmin(): bool
            {
                return false;
            }

            public function can($abilities, $arguments = []): bool
            {
                if ($abilities === 'cobros.all_branches') {
                    return $this->allBranches;
                }

                return in_array($abilities, [
                    'cobros.menu',
                    'cobros.read',
                    'cobros.financieras',
                    'cobros.prestamos',
                    'cobros.tandas',
                    'cobros.productos',
                    'financieras.read',
                    'prestamos.read',
                    'tandas.read',
                ], true);
            }
        };
    }

    public function test_date_filter_service_apply_recent_order_generates_deterministic_qualified_sql_and_formats_dates(): void
    {
        $finSql = DateFilterService::applyRecentOrder(FinSale::query(), 'sale_date', false)->toSql();
        $this->assertMatchesRegularExpression('/order by COALESCE\(fin_sales\.sale_date, fin_sales\.created_at\) DESC,\s*[`"]?fin_sales[`"]?\.[`"]?created_at[`"]? desc,\s*[`"]?fin_sales[`"]?\.[`"]?id[`"]? desc/i', $finSql);

        $loanSql = DateFilterService::applyRecentOrder(Loan::query(), 'start_date', true)->toSql();
        $this->assertMatchesRegularExpression('/order by DATE\(COALESCE\(loans\.start_date, loans\.created_at\)\) DESC,\s*[`"]?loans[`"]?\.[`"]?created_at[`"]? desc,\s*[`"]?loans[`"]?\.[`"]?id[`"]? desc/i', $loanSql);

        $tandaSql = DateFilterService::applyRecentOrder(Tanda::query(), 'start_date', true)->toSql();
        $this->assertMatchesRegularExpression('/order by DATE\(COALESCE\(tandas\.start_date, tandas\.created_at\)\) DESC,\s*[`"]?tandas[`"]?\.[`"]?created_at[`"]? desc,\s*[`"]?tandas[`"]?\.[`"]?id[`"]? desc/i', $tandaSql);

        $orderSql = DateFilterService::applyRecentOrder(Order::query(), 'order_date', true)->toSql();
        $this->assertMatchesRegularExpression('/order by DATE\(COALESCE\(orders\.order_date, orders\.created_at\)\) DESC,\s*[`"]?orders[`"]?\.[`"]?created_at[`"]? desc,\s*[`"]?orders[`"]?\.[`"]?id[`"]? desc/i', $orderSql);

        $this->assertSame('14/11/2026', DateFilterService::formatDisplayDate('2026-11-14 10:30:00'));
        $this->assertSame('09/11/2026', DateFilterService::formatDisplayDate(null, Carbon::parse('2026-11-09 08:00:00')));
        $this->assertSame('-', DateFilterService::formatDisplayDate(null, null));

        $this->expectException(\InvalidArgumentException::class);
        DateFilterService::applyRecentOrder(FinSale::query(), 'sale_date; DROP TABLE users', false);
    }

    public function test_unified_pos_and_loan_controllers_execute_most_recent_date_ordering_and_format_dates(): void
    {
        Auth::setUser($this->makePosUser(true));

        $recordingConnection = new class extends MySqlConnection {
            public array $recordedQueries = [];

            public function __construct()
            {
                parent::__construct(fn () => null, 'zya_test', '', ['driver' => 'mysql']);
            }

            public function select($query, $bindings = [], $useReadPdo = true): array
            {
                $this->recordedQueries[] = ['sql' => $query, 'bindings' => $bindings];
                preg_match('/\bfrom\s+`([a-z_]+)`/i', $query, $matches);
                $primaryTable = $matches[1] ?? '';

                if ($primaryTable === 'customers') {
                    return [
                        (object) [
                            'id' => 5,
                            'tit_name' => 'Roberto Fuentes',
                            'tit_phone' => '8110001111',
                        ],
                    ];
                }

                if ($primaryTable === 'fin_sales') {
                    return [
                        (object) [
                            'id' => 502,
                            'sale_code' => 'FIN-502',
                            'sale_date' => '2026-11-14 14:30:00',
                            'created_at' => '2026-11-14 14:30:00',
                            'device_id' => 2072,
                            'tag_contrato' => 'mxivnkb',
                            'financiera_id' => 1,
                            'branch_id' => 1,
                            'customer_name' => 'ROBERTO FUENTES RIOS',
                            'customer_phone' => '8111111111',
                            'price' => 4000,
                            'down_payment' => 500,
                            'enganche_descuento' => null,
                            'credit_amount' => 3500,
                            'abono_semanal' => 328,
                        ],
                        (object) [
                            'id' => 301,
                            'sale_code' => 'FIN-301',
                            'sale_date' => '2026-10-02 10:00:00',
                            'created_at' => '2026-10-02 10:00:00',
                            'device_id' => 1789,
                            'tag_contrato' => 'ND',
                            'financiera_id' => 1,
                            'branch_id' => 1,
                            'customer_name' => 'ROBERTO FUENTES RIOS',
                            'customer_phone' => '8111111111',
                            'price' => 3000,
                            'down_payment' => 300,
                            'enganche_descuento' => null,
                            'credit_amount' => 2700,
                            'abono_semanal' => 250,
                        ],
                    ];
                }

                if ($primaryTable === 'loans') {
                    return [
                        (object) [
                            'id' => 88,
                            'loan_code' => 'PREST-NEW',
                            'customer_id' => 5,
                            'loan_type_id' => 1,
                            'branch_id' => 1,
                            'operation_type' => 'prestamo',
                            'start_date' => '2026-11-14',
                            'created_at' => '2026-11-14 09:00:00',
                            'principal_amount' => 2000,
                            'total_amount' => 2400,
                            'paid_amount' => 0,
                            'balance_amount' => 2400,
                            'installment_amount' => 400,
                            'status' => 'activo',
                        ],
                    ];
                }

                if ($primaryTable === 'tandas') {
                    return [
                        (object) [
                            'id' => 77,
                            'description' => 'Tanda Noviembre',
                            'branch_id' => 1,
                            'start_date' => '2026-11-14',
                            'created_at' => '2026-11-14 08:00:00',
                            'payment_amount' => 500,
                            'duration' => 10,
                            'status' => 'activa',
                        ],
                    ];
                }

                if ($primaryTable === 'orders') {
                    return [
                        (object) [
                            'id' => 91,
                            'invoice_no' => 'INV-0091',
                            'customer_id' => 5,
                            'branch_id' => 1,
                            'order_date' => '2026-11-14',
                            'created_at' => '2026-11-14 11:00:00',
                            'total' => 900,
                            'pay' => 400,
                            'due' => 500,
                        ],
                    ];
                }

                return [];
            }
        };

        $originalResolver = FinSale::getConnectionResolver();
        $fakeResolver = new class($recordingConnection) implements ConnectionResolverInterface {
            private ConnectionInterface $conn;

            public function __construct(ConnectionInterface $conn)
            {
                $this->conn = $conn;
            }

            public function connection($name = null): ConnectionInterface
            {
                return $this->conn;
            }

            public function getDefaultConnection(): string
            {
                return 'mysql';
            }

            public function setDefaultConnection($name): void
            {
            }
        };

        FinSale::setConnectionResolver($fakeResolver);

        try {
            $posController = new UnifiedPosController();
            $loanController = new LoanController();

            // 1. Financieras lookup
            $finResponse = $posController->lookupFinanciera(Request::create('/central-pos/lookup-financiera', 'GET', [
                'q' => 'ROBERTO FUENTES',
            ]));
            $this->assertSame(200, $finResponse->getStatusCode());
            $finData = $finResponse->getData(true);
            $this->assertCount(2, $finData['results']);
            $this->assertSame('14/11/2026', $finData['results'][0]['sale_date_formatted']);
            $this->assertSame('02/10/2026', $finData['results'][1]['sale_date_formatted']);

            $finSql = collect($recordingConnection->recordedQueries)->firstWhere(fn ($q) => str_contains($q['sql'], 'from `fin_sales`'))['sql'] ?? '';
            $this->assertStringContainsString('COALESCE(fin_sales.sale_date, fin_sales.created_at) DESC, `fin_sales`.`created_at` desc, `fin_sales`.`id` desc', $finSql);
            $this->assertStringContainsString('limit 30', $finSql);

            // 2. Préstamos en POS
            $loanResponse = $posController->lookupCustomerLoans(Request::create('/central-pos/lookup-loans', 'GET', [
                'customer_id' => 5,
            ]));
            $this->assertSame(200, $loanResponse->getStatusCode());
            $loanData = $loanResponse->getData(true);
            $this->assertTrue($loanData['has_open_loans']);
            $this->assertSame('2026-11-14', $loanData['open_loans'][0]['start_date']);
            $this->assertSame('14/11/2026', $loanData['open_loans'][0]['sale_date_formatted']);

            $loanSql = collect($recordingConnection->recordedQueries)->firstWhere(fn ($q) => str_contains($q['sql'], 'from `loans`'))['sql'] ?? '';
            $this->assertStringContainsString('DATE(COALESCE(loans.start_date, loans.created_at)) DESC, `loans`.`created_at` desc, `loans`.`id` desc', $loanSql);

            // 3. Alerta de Préstamos en LoanController
            $recordingConnection->recordedQueries = [];
            $alertResponse = $loanController->checkCustomerAlerts(Request::create('/loans/customer-alerts', 'GET', [
                'customer_id' => 5,
            ]));
            $this->assertSame(200, $alertResponse->getStatusCode());
            $alertData = $alertResponse->getData(true);
            $this->assertSame('14/11/2026', $alertData['open_loans'][0]['sale_date_formatted']);
            $alertSql = collect($recordingConnection->recordedQueries)->firstWhere(fn ($q) => str_contains($q['sql'], 'from `loans`'))['sql'] ?? '';
            $this->assertStringContainsString('DATE(COALESCE(loans.start_date, loans.created_at)) DESC, `loans`.`created_at` desc, `loans`.`id` desc', $alertSql);

            // 4. Notas pendientes en POS
            $notesResponse = $posController->lookupCustomerPendingNotes(Request::create('/central-pos/lookup-pending-notes', 'GET', [
                'customer_id' => 5,
            ]));
            $this->assertSame(200, $notesResponse->getStatusCode());
            $notesSql = collect($recordingConnection->recordedQueries)->firstWhere(fn ($q) => str_contains($q['sql'], 'from `orders`'))['sql'] ?? '';
            $this->assertStringContainsString('DATE(COALESCE(orders.order_date, orders.created_at)) DESC, `orders`.`created_at` desc, `orders`.`id` desc', $notesSql);

            // 5. Tandas lookup (búsqueda por descripción o cliente/participante)
            $tandaResponse = $posController->lookupTanda(Request::create('/central-pos/lookup-tanda', 'GET', [
                'q' => 'Roberto',
            ]));
            $this->assertSame(200, $tandaResponse->getStatusCode());
            $tandaData = $tandaResponse->getData(true);
            $this->assertArrayHasKey('tandas', $tandaData);
            $this->assertArrayHasKey('results', $tandaData);
            $this->assertSame('14/11/2026', $tandaData['results'][0]['tanda_date_formatted']);

            $tandaSql = collect($recordingConnection->recordedQueries)->firstWhere(fn ($q) => str_contains($q['sql'], 'from `tandas`'))['sql'] ?? '';
            $this->assertStringContainsString('exists (select * from `tanda_clients`', $tandaSql);
            $this->assertStringContainsString('exists (select * from `customers`', $tandaSql);
            $this->assertStringContainsString('DATE(COALESCE(tandas.start_date, tandas.created_at)) DESC, `tandas`.`created_at` desc, `tandas`.`id` desc', $tandaSql);
        } finally {
            FinSale::setConnectionResolver($originalResolver);
        }
    }

    public function test_lookup_tanda_builds_participant_and_tanda_results_preserving_newest_tanda_order_and_next_period(): void
    {
        $controller = new UnifiedPosController();
        $method = new ReflectionMethod(UnifiedPosController::class, 'buildTandaLookupResults');
        $method->setAccessible(true);

        $linkedCustomer = tap(new Customer(['tit_name' => 'Roberto Fuentes', 'tit_phone' => '8110001111']), fn ($c) => $c->id = 4);

        $pRecent = tap(new TandaClient([
            'tanda_id' => 20,
            'customer_id' => 4,
            'customer_name' => 'Roberto Fuentes',
            'customer_phone' => '8110001111',
            'position' => 2,
            'payments' => [1 => 500, 2 => 500],
            'total_paid' => 1000,
        ]), function ($p) use ($linkedCustomer) {
            $p->id = 201;
            $p->setRelation('customer', $linkedCustomer);
        });

        $pOther = tap(new TandaClient([
            'tanda_id' => 20,
            'customer_name' => 'Maria Lopez',
            'customer_phone' => '8112223333',
            'position' => 1,
            'payments' => [],
            'total_paid' => 0,
        ]), fn ($p) => $p->id = 202);

        $pOlder = tap(new TandaClient([
            'tanda_id' => 10,
            'customer_name' => 'Roberto Fuentes',
            'customer_phone' => '8110001111',
            'position' => 5,
            'payments' => [1 => 300],
            'total_paid' => 300,
        ]), fn ($p) => $p->id = 105);

        $tandaRecent = tap(new Tanda([
            'description' => 'Tanda Navidad 2026',
            'start_date' => '2026-11-14',
            'payment_amount' => 500,
            'duration' => 10,
            'status' => 'activa',
        ]), function ($t) use ($pRecent, $pOther) {
            $t->id = 20;
            $t->created_at = Carbon::parse('2026-11-14 12:00:00');
            $t->setRelation('participants', collect([$pOther, $pRecent]));
            $t->setRelation('periods', collect());
        });

        $tandaOld = tap(new Tanda([
            'description' => 'Tanda Octubre 2026',
            'start_date' => '2026-10-05',
            'payment_amount' => 300,
            'duration' => 8,
            'status' => 'activa',
        ]), function ($t) use ($pOlder) {
            $t->id = 10;
            $t->created_at = Carbon::parse('2026-10-05 10:00:00');
            $t->setRelation('participants', collect([$pOlder]));
            $t->setRelation('periods', collect());
        });

        $tandasOrdered = collect([$tandaRecent, $tandaOld]);

        // Búsqueda por nombre de cliente -> devuelve coincidencias por participante manteniendo orden reciente -> antiguo
        $byParticipant = $method->invoke($controller, $tandasOrdered, 'Roberto');
        $this->assertCount(2, $byParticipant);
        $this->assertSame(20, $byParticipant[0]['tanda_id']);
        $this->assertSame('14/11/2026', $byParticipant[0]['tanda_date_formatted']);
        $this->assertSame(201, $byParticipant[0]['participant_id']);
        $this->assertSame('participant', $byParticipant[0]['matched_by']);
        $this->assertSame(2, $byParticipant[0]['paid_count']);
        $this->assertSame(3, $byParticipant[0]['next_period']);

        $this->assertSame(10, $byParticipant[1]['tanda_id']);
        $this->assertSame('05/10/2026', $byParticipant[1]['tanda_date_formatted']);
        $this->assertSame(105, $byParticipant[1]['participant_id']);
        $this->assertSame(2, $byParticipant[1]['next_period']);

        // Búsqueda por descripción de tanda -> devuelve entrada de tanda lista para elegir participante
        $byDescription = $method->invoke($controller, collect([$tandaRecent]), 'Navidad');
        $this->assertCount(1, $byDescription);
        $this->assertSame('tanda', $byDescription[0]['matched_by']);
        $this->assertNull($byDescription[0]['participant_id']);
        $this->assertSame('14/11/2026', $byDescription[0]['tanda_date_formatted']);
    }

    public function test_central_pos_view_compiles_and_renders_dates_and_tanda_customer_search_ui(): void
    {
        Gate::before(fn ($user, $ability) => method_exists($user, 'can') ? $user->can($ability) : false);
        Auth::setUser($this->makePosUser(true));

        $source = file_get_contents(resource_path('views/central-pos/index.blade.php'));
        $compiled = Blade::compileString($source);
        $this->assertNotEmpty($compiled);

        $tandaParticipant = tap(new TandaClient([
            'customer_name' => 'Roberto Fuentes',
            'customer_phone' => '8110001111',
            'position' => 1,
        ]), fn ($p) => $p->id = 55);

        $tanda = tap(new Tanda([
            'description' => 'Tanda Quincenal',
            'start_date' => '2026-11-14',
            'payment_amount' => 500,
        ]), function ($t) use ($tandaParticipant) {
            $t->id = 12;
            $t->setRelation('participants', collect([$tandaParticipant]));
        });

        $this->app->instance('request', Request::create('/central-pos', 'GET'));

        $html = view('central-pos.index', [
            'financieras' => collect([tap(new FinFinanciera(['name' => 'Krediya']), fn ($f) => $f->id = 1)]),
            'loanTypes' => collect([tap(new LoanType(['name' => 'Personal']), fn ($lt) => $lt->id = 1)]),
            'customers' => collect([tap(new Customer(['tit_name' => 'Roberto Fuentes', 'tit_phone' => '8110001111']), fn ($c) => $c->id = 4)]),
            'externalCustomers' => collect(),
            'canPosExternalCustomers' => false,
            'extCustomerStatusOptions' => [],
            'tandas' => collect([$tanda]),
            'products' => collect(),
            'gifts' => collect(),
            'branches' => collect([tap(new Branch(['name' => 'Allende']), fn ($b) => $b->id = 1)]),
            'branchId' => 1,
            'recentTransactions' => collect(),
        ])->render();

        // Financieras JS muestra fecha de venta
        $this->assertStringContainsString('Fecha: ${escapeHtml(r.sale_date_formatted', $html);
        // Préstamos JS muestra fecha de registro/inicio en el encabezado del crédito
        $this->assertStringContainsString('Fecha: ${escapeHtml(loan.sale_date_formatted', $html);
        // Tandas tiene buscador de cliente/participante, tabla de resultados y fecha en las opciones
        $this->assertStringContainsString('id="posTandaSearchInput"', $html);
        $this->assertStringContainsString('id="btnSearchTandaCustomer"', $html);
        $this->assertStringContainsString('id="posTandaSearchResults"', $html);
        $this->assertStringContainsString('renderTandaSearchResults', $html);
        $this->assertStringContainsString('ensureTandaOptionExists', $html);
        $this->assertStringContainsString('Tanda Quincenal (14/11/2026 · Aportación: $500.00)', $html);
    }
}
