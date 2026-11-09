<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\DailyCut;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoDailyCutCommand extends Command
{
    protected $signature = 'zya:daily-cut-auto {--date= : Fecha a cerrar en formato Y-m-d} {--force : Forzar corte ignorando hora programada} {--branch= : ID de sucursal específica}';

    protected $description = 'Aplica automáticamente el Corte Final (Corte Z) programado para cada sucursal según su hora configurada (si está activado).';

    public function handle(): int
    {
        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::today()->toDateString();
        $isToday = ($targetDate === Carbon::today()->toDateString());
        $nowTime = Carbon::now()->format('H:i');
        $force = (bool) $this->option('force');
        $branchOpt = $this->option('branch');

        $query = Branch::query();
        if ($branchOpt) {
            $query->where('id', $branchOpt);
        }
        $branches = $query->get();

        foreach ($branches as $branch) {
            // Si es la fecha de hoy y no se fuerza manualmente:
            if ($isToday && !$force) {
                // Si la sucursal tiene el corte automático desactivado, se respeta el modo manual
                if (!$branch->auto_cut_enabled) {
                    $this->line("Sucursal [{$branch->name}]: Corte automático desactivado (Modo manual). Omitiendo.");
                    continue;
                }

                // Si aún no es la hora programada para esta sucursal, omitir
                $scheduledTime = $branch->auto_cut_time ?: '22:00';
                if ($nowTime < $scheduledTime) {
                    $this->line("Sucursal [{$branch->name}]: Hora actual ({$nowTime}) menor a la hora de corte programada ({$scheduledTime}). Omitiendo.");
                    continue;
                }
            }

            $cut = self::closeBranchDayIfPending($branch->id, $targetDate, 'programado');
            if ($cut) {
                $this->info("Sucursal [{$branch->name}]: Corte Final Z aplicado exitosamente (ID #{$cut->id}).");
            } else {
                $this->line("Sucursal [{$branch->name}]: Sin movimientos pendientes de corte.");
            }
        }

        $this->info("Verificación de corte final completada para la fecha {$targetDate}.");
        return self::SUCCESS;
    }

    public static function closeBranchDayIfPending(int $branchId, string $dateStr, string $mode = 'programado', ?int $userId = null): ?DailyCut
    {
        // Verificar si ya existe un Corte Final (corte_z) para esa sucursal y fecha
        $existingFinal = DailyCut::where('branch_id', $branchId)
            ->whereDate('date', $dateStr)
            ->where('cut_type', 'corte_z')
            ->first();

        if ($existingFinal) {
            return $existingFinal;
        }

        $flows = CashFlow::where('branch_id', $branchId)
            ->whereDate('created_at', $dateStr)
            ->get();

        // Si es de un día pasado y no hubo movimientos ni corte, no contaminar salvo que sea hoy
        if ($flows->isEmpty() && $dateStr < Carbon::today()->toDateString()) {
            return null;
        }

        $lastCut = DailyCut::where('branch_id', $branchId)
            ->where('cut_type', 'corte_z')
            ->whereDate('date', '<', $dateStr)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();

        $openingBalance = $lastCut ? (float) $lastCut->balance : 0.0;
        $incomes = $flows->where('type', 'income');
        $expenses = $flows->where('type', 'expense');

        $totalIncome = round((float) $incomes->sum('amount'), 2);
        $totalExpense = round((float) $expenses->sum('amount'), 2);
        $cashIncome = round((float) $incomes->sum(fn($f) => (float) $f->cash_amount > 0 ? $f->cash_amount : ($f->payment_method === 'efectivo' || empty($f->payment_method) ? $f->amount : 0)), 2);
        $cardIncome = round((float) $incomes->sum(fn($f) => (float) $f->card_amount > 0 ? $f->card_amount : ($f->payment_method === 'tarjeta' ? $f->amount : 0)), 2);
        $transferIncome = round((float) $incomes->sum(fn($f) => (float) $f->transfer_amount > 0 ? $f->transfer_amount : ($f->payment_method === 'transferencia' ? $f->amount : 0)), 2);

        $finalBalance = round($openingBalance + $totalIncome - $totalExpense, 2);

        // Desglose nominal por cliente
        $customerBreakdown = $flows->groupBy(fn($f) => $f->customer_name ?: 'Público en General / Operación Interna')
            ->map(function ($group, $clientName) {
                return [
                    'customer_name' => $clientName,
                    'movements_count' => $group->count(),
                    'total_income' => round((float) $group->where('type', 'income')->sum('amount'), 2),
                    'total_expense' => round((float) $group->where('type', 'expense')->sum('amount'), 2),
                    'modules' => $group->pluck('module')->filter()->unique()->values()->toArray(),
                ];
            })->values()->toArray();

        $cut = DailyCut::create([
            'branch_id' => $branchId,
            'date' => $dateStr,
            'cut_type' => 'corte_z',
            'cut_mode' => $mode,
            'user_id' => $userId,
            'opening_balance' => $openingBalance,
            'total_income' => $totalIncome,
            'cash_income' => $cashIncome,
            'card_income' => $cardIncome,
            'transfer_income' => $transferIncome,
            'total_expense' => $totalExpense,
            'withdraw_amount' => 0,
            'balance' => $finalBalance,
            'declared_cash' => $cashIncome,
            'difference_amount' => 0,
            'customer_breakdown' => $customerBreakdown,
            'notes' => $mode === 'programado' ? 'Corte final diario aplicado por default programado.' : 'Corte final manual.',
            'closed_at' => now(),
        ]);

        CashFlow::where('branch_id', $branchId)
            ->whereDate('created_at', $dateStr)
            ->whereNull('daily_cut_id')
            ->update(['daily_cut_id' => $cut->id]);

        return $cut;
    }
}

