<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PosHardwareAndDrawerBugfixTest extends TestCase
{
    public function test_canonical_pos_hardware_js_provides_mutex_port_selection_and_iframe_delegation(): void
    {
        $jsPath = public_path('assets/js/pos-hardware.js');
        $this->assertFileExists($jsPath);

        $js = file_get_contents($jsPath);

        $this->assertStringContainsString('KICK_DRAWER_BYTES', $js);
        $this->assertStringContainsString('KICK_BYTES', $js);
        $this->assertStringContainsString('VALID_BAUD_RATES', $js);
        $this->assertStringContainsString('global.parent.ZyaPosHardware', $js);
        $this->assertStringContainsString('_kickQueue', $js);
        $this->assertStringContainsString('zya_drawer_last_kick_at', $js);
        $this->assertStringContainsString('zya_serial_port_pref', $js);
        $this->assertStringContainsString('_orderCandidatePorts', $js);
        $this->assertStringContainsString('forceSelect', $js);
        $this->assertStringContainsString('waitUntilIdle', $js);
        $this->assertStringContainsString('autoOpenUsbDrawerBounded', $js);
        $this->assertStringContainsString('manualOpenUsbDrawer', $js);
        $this->assertStringContainsString('wasRecentlyKickedAcrossPages', $js);
    }

    public function test_views_use_canonical_pos_hardware_without_duplicate_inline_definitions_or_dead_escpos_spans(): void
    {
        $views = [
            'dashboard/body/main.blade.php',
            'central-pos/open-drawer.blade.php',
            'central-pos/receipt.blade.php',
            'loans/ticket.blade.php',
            'orders/abono-ticket.blade.php',
        ];

        foreach ($views as $relativeView) {
            $source = file_get_contents(resource_path('views/' . $relativeView));
            $compiled = Blade::compileString($source);
            $this->assertNotEmpty($compiled, "Blade compilation failed for {$relativeView}");
            $this->assertStringContainsString('assets/js/pos-hardware.js', $source, "{$relativeView} should load pos-hardware.js");
            $this->assertStringNotContainsString('KICK_BYTES = new Uint8Array', $source, "{$relativeView} should not duplicate KICK_BYTES");
            $this->assertStringNotContainsString('&#27;&#112;', $source, "{$relativeView} should not contain broken HTML ESC/POS span");
        }
    }

    public function test_open_drawer_view_exposes_baud_rate_and_usb_port_switch_controls(): void
    {
        $source = file_get_contents(resource_path('views/central-pos/open-drawer.blade.php'));
        $compiled = Blade::compileString($source);
        $this->assertNotEmpty($compiled);

        $this->assertStringContainsString('baudRateSelect', $source);
        $this->assertStringContainsString('btnSwitchUsbPort', $source);
        $this->assertStringContainsString('manualOpenUsbDrawer', $source);
        $this->assertStringContainsString('forceSelect: true', $source);
    }

    public function test_cash_views_expose_manual_drawer_button_and_safe_async_form_submit_hooks(): void
    {
        $cashIndex = file_get_contents(resource_path('views/cash/index.blade.php'));
        $this->assertNotEmpty(Blade::compileString($cashIndex));
        $this->assertStringContainsString("route('cash.openDrawer')", $cashIndex);
        $this->assertStringContainsString('Abrir Cajón Físico', $cashIndex);
        $this->assertStringContainsString("session('open_cash_drawer')", $cashIndex);
        $this->assertStringContainsString('autoOpenUsbDrawer', $cashIndex);

        $dailyCut = file_get_contents(resource_path('views/cash/daily-cut.blade.php'));
        $this->assertNotEmpty(Blade::compileString($dailyCut));
        $this->assertStringContainsString("route('cash.openDrawer')", $dailyCut);
        $this->assertStringContainsString('Abrir Cajón Físico', $dailyCut);
        $this->assertStringContainsString('autoOpenUsbDrawerBounded(600)', $dailyCut);
        $this->assertStringContainsString('dataset.drawerSubmitting', $dailyCut);
    }

    public function test_pos_checkout_and_print_layout_coordinate_drawer_print_and_modal_focus(): void
    {
        $main = file_get_contents(resource_path('views/dashboard/body/main.blade.php'));
        $this->assertStringContainsString('waitUntilIdle(700)', $main);
        $this->assertStringContainsString('onPrintTriggered', $main);

        $posIndex = file_get_contents(resource_path('views/central-pos/index.blade.php'));
        $this->assertNotEmpty(Blade::compileString($posIndex));
        $this->assertStringContainsString("route('central-pos.open-drawer')", $posIndex);
        $this->assertStringContainsString('btnOpenPhysicalDrawer', $posIndex);
        $this->assertStringContainsString('autoOpenUsbDrawerBounded(700)', $posIndex);
        $this->assertStringContainsString('onPrintTriggered', $posIndex);
        $this->assertStringContainsString("'skip_drawer' => 1", $posIndex);
    }

    public function test_reprints_mark_skip_drawer_and_financieras_loans_auto_print(): void
    {
        $dashboardIndex = file_get_contents(resource_path('views/dashboard/index.blade.php'));
        $this->assertNotEmpty(Blade::compileString($dashboardIndex));
        $this->assertStringContainsString('skip_drawer=1', $dashboardIndex);

        $financieraShow = file_get_contents(resource_path('views/financieras/ventas/show.blade.php'));
        $this->assertNotEmpty(Blade::compileString($financieraShow));
        $this->assertStringContainsString('skip_drawer=1', $financieraShow);
        $this->assertStringContainsString("session('print_receipt_url')", $financieraShow);
        $this->assertStringContainsString('printTicketDirectly', $financieraShow);

        $loanShow = file_get_contents(resource_path('views/loans/show.blade.php'));
        $this->assertNotEmpty(Blade::compileString($loanShow));
        $this->assertStringContainsString('skip_drawer=1', $loanShow);
        $this->assertStringContainsString("session('print_ticket_url')", $loanShow);
        $this->assertStringContainsString('printTicketDirectly', $loanShow);

        $pendingDue = file_get_contents(resource_path('views/orders/pending-due.blade.php'));
        $this->assertNotEmpty(Blade::compileString($pendingDue));
        $this->assertStringContainsString('skip_drawer=1', $pendingDue);
        $this->assertStringContainsString('payDueForm', $pendingDue);
        $this->assertStringContainsString('autoOpenUsbDrawerBounded(600)', $pendingDue);
        $this->assertStringContainsString('wasRecentlyKickedAcrossPages(10000)', $pendingDue);
    }

    public function test_routes_and_controllers_expose_cash_drawer_and_receipt_permissions(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $this->assertStringContainsString("->name('cash.openDrawer')", $routes);
        $this->assertStringContainsString('cash.open_drawer', $routes);
        $this->assertStringContainsString('financieras.ventas.abonar', $routes);
        $this->assertStringContainsString('dashboard.read', $routes);

        $cashController = file_get_contents(app_path('Http/Controllers/Dashboard/CashController.php'));
        $this->assertStringContainsString('open_cash_drawer', $cashController);
        $this->assertStringContainsString('function openDrawer', $cashController);
        $this->assertStringContainsString("can('cash.open_drawer')", $cashController);

        $posController = file_get_contents(app_path('Http/Controllers/Dashboard/UnifiedPosController.php'));
        $this->assertStringContainsString('financieras.ventas.abonar', $posController);
        $this->assertStringContainsString("'drawer_opened' =>", $posController);
    }
}
