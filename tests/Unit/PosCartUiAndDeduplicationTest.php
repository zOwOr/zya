<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PosCartUiAndDeduplicationTest extends TestCase
{
    public function test_central_pos_cart_contains_grouped_ui_steppers_and_deduplication_engine(): void
    {
        $path = resource_path('views/central-pos/index.blade.php');
        $source = file_get_contents($path);
        $this->assertIsString($source);

        $compiled = Blade::compileString($source);
        $this->assertNotEmpty($compiled);

        // Estructura visual organizada y resumen de conceptos/unidades
        $this->assertStringContainsString('id="posCartSummaryBar"', $source);
        $this->assertStringContainsString('id="posCartFeedbackBanner"', $source);
        $this->assertStringContainsString('class="cart-items-list"', $source);
        $this->assertStringContainsString('CART_GROUPS', $source);
        $this->assertStringContainsString('cart-group-header', $source);
        $this->assertStringContainsString('cart-item-card', $source);
        $this->assertStringContainsString('cart-qty-stepper', $source);
        $this->assertStringContainsString('btn-qty-minus', $source);
        $this->assertStringContainsString('btn-qty-plus', $source);

        // Prevención de duplicados y consolidación automática de carrito persistido
        $this->assertStringContainsString('function normalizeTextKey', $source);
        $this->assertStringContainsString('function findMatchingCartIndex', $source);
        $this->assertStringContainsString('function consolidateTabItems', $source);
        $this->assertStringContainsString('consolidateTabItems(t);', $source);
        $this->assertStringContainsString('function updateCartItemQuantity', $source);
        $this->assertStringContainsString('showCartFeedback(', $source);
    }
}

