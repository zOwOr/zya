<?php

namespace App\Services;

use App\Models\CashFlow;

class CashFlowService
{
public static function register($type, $amount, $description = null, $reference = null, $module = null, $branch_id)
{
    return CashFlow::create([
        'type' => $type,
        'amount' => $amount,
        'description' => $description,
        'reference' => $reference,
        'module' => $module,
        'branch_id' => $branch_id,  // asigna branch_id aquí
    ]);
}

}

