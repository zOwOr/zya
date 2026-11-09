<?php

namespace App\Services;

use App\Models\CashFlow;

class CashFlowService
{
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
