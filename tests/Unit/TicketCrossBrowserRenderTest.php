<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Order;
use App\Models\PaymentHistory;
use App\Models\PosTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class TicketCrossBrowserRenderTest extends TestCase
{
    protected function makeSampleTransaction(): PosTransaction
    {
        $branch = new Branch(['name' => 'Allende']);
        $user = new User(['name' => 'Cajero Prueba']);

        $tx = new PosTransaction([
            'transaction_code' => 'MOV-20261113-XB01',
            'customer_name' => 'Juan Perez',
            'payment_method' => 'mixto',
            'cash_amount' => 500,
            'card_amount' => 300,
            'transfer_amount' => 200,
            'subtotal' => 1000,
            'commission_total' => 0,
            'total_amount' => 1000,
            'paid_amount' => 1000,
            'change_amount' => 0,
            'items_payload' => [
                [
                    'section' => 'producto',
                    'title' => 'Funda Antigolpes',
                    'subtitle' => 'Modelo Pro',
                    'quantity' => 2,
                    'amount' => 150,
                    'commission' => 0,
                ],
                [
                    'section' => 'gift',
                    'title' => 'Mica Hidrogel',
                    'subtitle' => 'Cortesía | Proveedor: Mayorista XYZ',
                    'quantity' => 1,
                    'amount' => 0,
                    'commission' => 0,
                ],
            ],
        ]);
        $tx->created_at = Carbon::parse('2026-11-13 14:30:00');
        $tx->setRelation('branch', $branch);
        $tx->setRelation('user', $user);
        $tx->setRelation('financieraPayments', collect());

        return $tx;
    }

    public function test_central_pos_receipt_renders_clean_cross_browser_print_styles_and_ssr_paper_modes(): void
    {
        $tx = $this->makeSampleTransaction();

        $this->app->instance('request', Request::create('/central-pos/receipt/1', 'GET', ['paper' => '58']));
        $html58 = View::make('central-pos.receipt', ['transaction' => $tx])->render();
        $this->assertStringContainsString('<body id="ticketBody" class="paper-58mm">', $html58);
        $this->assertStringNotContainsString('overflow: hidden !important;', $html58);
        $this->assertStringContainsString('overflow-x: hidden !important;', $html58);
        $this->assertStringContainsString('overflow-y: visible !important;', $html58);
        $this->assertStringNotContainsString('Mayorista', $html58);
        $this->assertStringContainsString('safeStorageGet', $html58);
        $this->assertSame(1, substr_count($html58, 'localStorage.getItem('));
        $this->assertStringNotContainsString('@media print { @page', $html58);

        $this->app->instance('request', Request::create('/central-pos/receipt/1', 'GET', ['paper' => 'letter']));
        $htmlLetter = View::make('central-pos.receipt', ['transaction' => $tx])->render();
        $this->assertStringContainsString('<body id="ticketBody" class="paper-letter">', $htmlLetter);
        $this->assertStringContainsString('<style id="dynamicPageRule">@page { size: letter portrait; margin: 10mm; }</style>', $htmlLetter);
        $this->assertStringContainsString('waitBeforeAutoPrint()', $htmlLetter);
    }

    public function test_loan_and_abono_tickets_render_safe_storage_and_unclipped_print_css(): void
    {
        $branch = new Branch(['name' => 'Montemorelos', 'address' => 'Centro 123', 'phone' => '8261234567']);
        $customer = new Customer(['tit_name' => 'Maria Lopez', 'tit_phone' => '8261112233']);
        $user = new User(['name' => 'Cajero Dos']);
        Auth::setUser($user);

        $loan = new Loan(['loan_code' => 'PREST-001', 'branch_id' => 1]);
        $loan->setRelation('branch', $branch);
        $loan->setRelation('customer', $customer);

        $loanPayment = new LoanPayment([
            'payment_code' => 'REC-001',
            'payment_type' => 'abono',
            'payment_method' => 'efectivo',
            'amount' => 250,
        ]);
        $loanPayment->paid_at = Carbon::parse('2026-11-13 12:00:00');
        $loanPayment->setRelation('loan', $loan);
        $loanPayment->setRelation('user', $user);

        $this->app->instance('request', Request::create('/loans/ticket/1', 'GET', ['paper' => '58']));
        $loanHtml = View::make('loans.ticket', ['payment' => $loanPayment])->render();
        $this->assertStringContainsString('<body id="ticketBody" class="paper-58mm">', $loanHtml);
        $this->assertStringNotContainsString('overflow: hidden !important;', $loanHtml);
        $this->assertStringContainsString('overflow-y: visible !important;', $loanHtml);
        $this->assertStringContainsString('safeStorageGet', $loanHtml);
        $this->assertSame(1, substr_count($loanHtml, 'localStorage.getItem('));

        $order = new Order([
            'invoice_no' => 'NV-100',
            'order_date' => '2026-11-13',
            'total' => 1000,
            'due' => 400,
        ]);
        $order->setRelation('customer', $customer);

        $history = new PaymentHistory([
            'id' => 7,
            'amount' => 300,
            'method' => 'HandCash',
            'cash_amount' => 300,
            'card_amount' => 0,
            'transfer_amount' => 0,
        ]);
        $history->id = 7;
        $history->created_at = Carbon::parse('2026-11-13 13:00:00');
        $history->setRelation('user', $user);

        $this->app->instance('request', Request::create('/orders/1/ticket-abono/7', 'GET', ['paper' => '58']));
        $abonoHtml = View::make('orders.abono-ticket', [
            'order' => $order,
            'history' => $history,
            'branch' => $branch,
        ])->render();
        $this->assertStringContainsString('<body id="ticketBody" class="paper-58mm">', $abonoHtml);
        $this->assertStringNotContainsString('overflow: hidden !important;', $abonoHtml);
        $this->assertStringContainsString('overflow-y: visible !important;', $abonoHtml);
        $this->assertStringContainsString('safeStorageGet', $abonoHtml);
        $this->assertSame(1, substr_count($abonoHtml, 'localStorage.getItem('));
    }

    public function test_global_print_engine_uses_cross_browser_safeguards(): void
    {
        $mainBlade = file_get_contents(resource_path('views/dashboard/body/main.blade.php'));
        $posBlade = file_get_contents(resource_path('views/central-pos/index.blade.php'));

        $this->assertIsString($mainBlade);
        $this->assertIsString($posBlade);

        // No debe seguir usando iframe fuera de pantalla (-9999px) ni modal con enforceFocus robando el foco
        $this->assertStringNotContainsString('-9999px', $mainBlade);
        $this->assertStringContainsString("frame.style.opacity = '0.001'", $mainBlade);
        $this->assertStringContainsString("execCommand('print', false, null)", $mainBlade);
        $this->assertStringContainsString('isTouchMobile', $mainBlade);
        $this->assertStringContainsString('focus: false', $mainBlade);
        $this->assertStringContainsString('zyaTicketStorage', $mainBlade);
        $this->assertStringContainsString('normalizeTicketUrl', $mainBlade);

        // En central-pos/index.blade.php el acceso a zya_ticket_paper_mode debe existir únicamente en el wrapper seguro getTicketPaperMode()
        $this->assertSame(1, substr_count($posBlade, "localStorage.getItem('zya_ticket_paper_mode')"));
        $this->assertStringContainsString('getTicketPaperMode()', $posBlade);
    }
}
