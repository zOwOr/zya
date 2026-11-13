<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\Order;
use App\Models\PosTransaction;
use Illuminate\Support\Str;

class CashFlowService
{
    public const CANCELLATION_MODULE = 'Cancelación de Venta';

    /**
     * Normaliza variantes heredadas o localizadas del método de pago a valores canónicos de caja.
     */
    public static function normalizePaymentMethod(?string $method): string
    {
        $normalized = strtolower(trim((string) $method));

        return match ($normalized) {
            'efectivo', 'handcash', 'cash', '' => 'efectivo',
            'transferencia', 'cheque', 'transfer' => 'transferencia',
            'tarjeta', 'due', 'card' => 'tarjeta',
            'mixto', 'mixed' => 'mixto',
            default => $normalized,
        };
    }

    /**
     * Calcula el efectivo físico neto pendiente por revertir de un PosTransaction según sus ingresos en caja.
     */
    public static function resolveCashFromPosTransaction(PosTransaction $transaction): float
    {
        $incomeFlows = CashFlow::where('type', 'income')
            ->where('pos_transaction_id', $transaction->id)
            ->lockForUpdate()
            ->get();

        if ($incomeFlows->isEmpty() && !empty($transaction->transaction_code)) {
            $incomeFlows = CashFlow::where('type', 'income')
                ->whereNull('pos_transaction_id')
                ->where('branch_id', $transaction->branch_id)
                ->where('reference', $transaction->transaction_code)
                ->lockForUpdate()
                ->get();
        }

        if ($incomeFlows->isEmpty() && !empty($transaction->order_id)) {
            $incomeFlows = CashFlow::where('type', 'income')
                ->whereNull('pos_transaction_id')
                ->where('branch_id', $transaction->branch_id)
                ->whereIn('module', ['Orden', 'ventas'])
                ->where('reference', (string) $transaction->order_id)
                ->lockForUpdate()
                ->get();
        }

        if ($incomeFlows->isEmpty()) {
            return 0.0;
        }

        $totalCashIn = round((float) $incomeFlows->sum(fn ($flow) => $flow->effective_cash_amount), 2);
        if ($totalCashIn <= 0) {
            return 0.0;
        }

        $alreadyReversed = round((float) CashFlow::where('type', 'expense')
            ->where(function ($query) use ($transaction) {
                $query->where('pos_transaction_id', $transaction->id);
                if (!empty($transaction->transaction_code)) {
                    $query->orWhere(function ($sub) use ($transaction) {
                        $sub->where('module', self::CANCELLATION_MODULE)
                            ->where('branch_id', $transaction->branch_id)
                            ->where('reference', $transaction->transaction_code);
                    });
                }
            })
            ->lockForUpdate()
            ->get()
            ->sum(fn ($flow) => $flow->effective_cash_amount), 2);

        return round(max(0.0, $totalCashIn - $alreadyReversed), 2);
    }

    /**
     * Registra un egreso en efectivo idempotente al cancelar un PosTransaction cuando incluyó efectivo.
     */
    public static function revertPosTransactionCash(PosTransaction $transaction, ?string $reason = null): ?CashFlow
    {
        $cashToRevert = self::resolveCashFromPosTransaction($transaction);
        if ($cashToRevert <= 0) {
            return null;
        }

        $code = $transaction->transaction_code ?: ('POS-' . $transaction->id);
        $description = "Devolución en efectivo por cancelación de venta {$code}"
            . ($transaction->customer_name ? " ({$transaction->customer_name})" : '')
            . ($reason ? " - {$reason}" : '');

        $branchId = $transaction->branch_id ?: (auth()->check() ? auth()->user()->branch_id : 1);

        return self::register(
            'expense',
            $cashToRevert,
            Str::limit($description, 245),
            $code,
            self::CANCELLATION_MODULE,
            $branchId,
            [
                'pos_transaction_id' => $transaction->id,
                'customer_id' => $transaction->customer_id,
                'customer_name' => $transaction->customer_name,
                'payment_method' => 'efectivo',
                'cash_amount' => $cashToRevert,
                'card_amount' => 0,
                'transfer_amount' => 0,
            ]
        );
    }

    /**
     * Revierte el efectivo físico recibido en caja por una orden (pago inicial, abonos y POS vinculado sin duplicar).
     */
    public static function revertOrderCash(Order $order, ?string $reason = null): float
    {
        $revertedTotal = 0.0;
        $orderRef = $order->invoice_no ?: ('ORD-' . $order->id);
        $customerName = $order->customer?->tit_name ?? 'Cliente Mostrador';
        $fallbackBranchId = $order->resolved_branch?->id
            ?? $order->branch_id
            ?? (auth()->check() ? auth()->user()->branch_id : 1);

        $directIncomeFlows = CashFlow::where('type', 'income')
            ->where(function ($query) use ($order) {
                $query->where(function ($sub) use ($order) {
                    $sub->whereIn('module', ['Orden', 'ventas'])
                        ->where('reference', (string) $order->id);
                });

                if (!empty($order->invoice_no)) {
                    $query->orWhere(function ($sub) use ($order) {
                        $sub->where('module', 'Abono Nota Venta')
                            ->where('reference', $order->invoice_no);
                    });
                }
            })
            ->lockForUpdate()
            ->get();

        $directCashIn = round((float) $directIncomeFlows->sum(fn ($flow) => $flow->effective_cash_amount), 2);
        if ($directCashIn > 0) {
            $directBranchId = $directIncomeFlows->first()?->branch_id ?: $fallbackBranchId;
            $alreadyReversedDirect = round((float) CashFlow::where('type', 'expense')
                ->where('module', self::CANCELLATION_MODULE)
                ->where('branch_id', $directBranchId)
                ->whereIn('reference', array_values(array_unique([$orderRef, 'ORD-' . $order->id])))
                ->lockForUpdate()
                ->get()
                ->sum(fn ($flow) => $flow->effective_cash_amount), 2);

            $directToRevert = round(max(0.0, $directCashIn - $alreadyReversedDirect), 2);
            if ($directToRevert > 0) {
                $desc = "Devolución en efectivo por cancelación de orden {$orderRef}"
                    . ($customerName ? " ({$customerName})" : '')
                    . ($reason ? " - {$reason}" : '');

                self::register(
                    'expense',
                    $directToRevert,
                    Str::limit($desc, 245),
                    $orderRef,
                    self::CANCELLATION_MODULE,
                    $directBranchId,
                    [
                        'customer_id' => $order->customer_id,
                        'customer_name' => $customerName,
                        'payment_method' => 'efectivo',
                        'cash_amount' => $directToRevert,
                        'card_amount' => 0,
                        'transfer_amount' => 0,
                    ]
                );

                $revertedTotal = round($revertedTotal + $directToRevert, 2);
            }
        }

        $posTx = PosTransaction::where('order_id', $order->id)->lockForUpdate()->first();
        if ($posTx && $posTx->status !== 'cancelado') {
            $availableTxCash = self::resolveCashFromPosTransaction($posTx);
            $sections = collect($posTx->items_payload ?? [])->pluck('section')->filter()->values();
            $isPureProductTx = $sections->isEmpty() || $sections->every(
                fn ($section) => in_array($section, ['producto_inventario', 'producto_dinamico', 'gift'], true)
            );

            if ($isPureProductTx) {
                $txNote = 'Cancelado vía orden ' . $orderRef . ' por ' . (auth()->user()?->name ?? 'Sistema') . ' el ' . now()->format('d/m/Y H:i');
                $posTx->update([
                    'status' => 'cancelado',
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'cancellation_reason' => $reason ?: $posTx->cancellation_reason,
                    'notes' => trim(($posTx->notes ? $posTx->notes . ' | ' : '') . $txNote),
                ]);

                $txReversal = self::revertPosTransactionCash($posTx, $reason ?: "Cancelación de orden {$orderRef}");
                if ($txReversal) {
                    $revertedTotal = round($revertedTotal + (float) $txReversal->amount, 2);
                }
            } elseif ($availableTxCash > 0) {
                $ratio = ((float) $posTx->total_amount > 0)
                    ? min(1.0, max(0.0, (float) $order->total / (float) $posTx->total_amount))
                    : 1.0;
                $orderShareCash = min($availableTxCash, round($availableTxCash * $ratio, 2));

                if ($orderShareCash > 0) {
                    $desc = "Devolución en efectivo por cancelación de orden {$orderRef} ({$posTx->transaction_code})"
                        . ($customerName ? " ({$customerName})" : '');

                    self::register(
                        'expense',
                        $orderShareCash,
                        Str::limit($desc, 245),
                        $orderRef,
                        self::CANCELLATION_MODULE,
                        $posTx->branch_id ?: $fallbackBranchId,
                        [
                            'pos_transaction_id' => $posTx->id,
                            'customer_id' => $order->customer_id,
                            'customer_name' => $customerName,
                            'payment_method' => 'efectivo',
                            'cash_amount' => $orderShareCash,
                            'card_amount' => 0,
                            'transfer_amount' => 0,
                        ]
                    );

                    $revertedTotal = round($revertedTotal + $orderShareCash, 2);
                }
            }
        }

        return $revertedTotal;
    }

    public static function register(
        $type,
        $amount,
        $description = null,
        $reference = null,
        $module = null,
        $branch_id = null,
        array $extra = []
    ) {
        $branchId = $branch_id ?? (auth()->check() ? auth()->user()->branch_id : 1);
        $userId = $extra['user_id'] ?? (auth()->check() ? auth()->id() : null);
        $method = $extra['payment_method'] ?? 'efectivo';
        $numericAmount = (float) $amount;

        $cashAmount = isset($extra['cash_amount']) ? (float) $extra['cash_amount'] : ($method === 'efectivo' ? $numericAmount : 0);
        $cardAmount = isset($extra['card_amount']) ? (float) $extra['card_amount'] : ($method === 'tarjeta' ? $numericAmount : 0);
        $transferAmount = isset($extra['transfer_amount']) ? (float) $extra['transfer_amount'] : ($method === 'transferencia' ? $numericAmount : 0);

        return CashFlow::create([
            'type' => $type,
            'amount' => $numericAmount,
            'description' => $description,
            'reference' => $reference,
            'module' => $module,
            'expense_type_id' => $extra['expense_type_id'] ?? null,
            'expense_type_name' => $extra['expense_type_name'] ?? null,
            'branch_id' => $branchId,
            'user_id' => $userId,
            'pos_transaction_id' => $extra['pos_transaction_id'] ?? null,
            'customer_id' => $extra['customer_id'] ?? null,
            'customer_name' => $extra['customer_name'] ?? null,
            'payment_method' => $method,
            'cash_amount' => $cashAmount,
            'card_amount' => $cardAmount,
            'transfer_amount' => $transferAmount,
            'commission_amount' => $extra['commission_amount'] ?? 0,
        ]);
    }
}
