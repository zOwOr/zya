<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\CashFlow;
use App\Models\DailyCut;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateDailyCutsCommand extends Command
{
    protected $signature = 'zya:recalculate-cuts
        {--from=2026-11-08 : Fecha inicial en formato Y-m-d}
        {--to= : Fecha final en formato Y-m-d (por defecto hoy)}
        {--branch= : ID o nombre de sucursal específica}
        {--dry-run : Simular y mostrar los ajustes sin escribir en base de datos}';

    protected $description = 'Recalcula en cadena los Cortes de Caja (X y Z) desde la fecha indicada sin borrar el historial, asegurando que el saldo inicial sea el efectivo físico declarado anterior y excluyendo transferencias/tarjeta del efectivo esperado.';

    public function handle(): int
    {
        $fromDate = Carbon::parse($this->option('from'))->toDateString();
        $toDate = $this->option('to')
            ? Carbon::parse($this->option('to'))->toDateString()
            : Carbon::today()->toDateString();
        $branchOpt = $this->option('branch');
        $dryRun = (bool) $this->option('dry-run');

        $branchQuery = Branch::query()->orderBy('id');
        if ($branchOpt !== null && $branchOpt !== '') {
            if (is_numeric($branchOpt)) {
                $branchQuery->where('id', (int) $branchOpt);
            } else {
                $branchQuery->where('name', 'like', "%{$branchOpt}%");
            }
        }

        $branches = $branchQuery->get();
        if ($branches->isEmpty()) {
            $this->warn('No se encontraron sucursales para procesar.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('=== MODO DRY-RUN (SIMULACIÓN): No se guardarán cambios en la base de datos ===');
        }

        $rowsSummary = [];
        $reclassifiedFlowsCount = 0;

        $runner = function () use ($branches, $fromDate, $toDate, &$rowsSummary, &$reclassifiedFlowsCount) {
            foreach ($branches as $branch) {
                // 1. Detectar fechas con movimientos o cortes en el rango para esta sucursal
                $cutDates = DailyCut::where('branch_id', $branch->id)
                    ->whereDate('date', '>=', $fromDate)
                    ->whereDate('date', '<=', $toDate)
                    ->orderBy('date')
                    ->pluck('date')
                    ->map(fn($d) => Carbon::parse($d)->toDateString());

                $flowDates = CashFlow::where('branch_id', $branch->id)
                    ->whereDate('created_at', '>=', $fromDate)
                    ->whereDate('created_at', '<=', $toDate)
                    ->selectRaw('DATE(created_at) as d')
                    ->distinct()
                    ->pluck('d')
                    ->map(fn($d) => Carbon::parse($d)->toDateString());

                $allDates = $cutDates->merge($flowDates)->unique()->sort()->values();

                foreach ($allDates as $dateStr) {
                    // Verificar el Corte Z inmediato anterior para detectar si en esta fecha se registró
                    // un egreso artificial por el monto exacto de las transferencias del día anterior
                    $prevCut = DailyCut::where('branch_id', $branch->id)
                        ->where('cut_type', 'corte_z')
                        ->whereDate('date', '<', $dateStr)
                        ->orderByDesc('date')
                        ->orderByDesc('id')
                        ->first();

                    $prevTransfer = $prevCut ? round((float) $prevCut->transfer_income, 2) : 0.0;

                    $dayFlows = CashFlow::where('branch_id', $branch->id)
                        ->whereDate('created_at', $dateStr)
                        ->orderBy('id')
                        ->get();

                    foreach ($dayFlows->where('type', 'expense') as $expFlow) {
                        $method = strtolower((string) $expFlow->payment_method);
                        $amt = round((float) $expFlow->amount, 2);
                        $descLower = mb_strtolower((string) $expFlow->description);

                        $matchesPrevTransfer = ($prevTransfer > 0 && abs($amt - $prevTransfer) < 0.01);
                        $mentionsTransfer = str_contains($descLower, 'transferencia');

                        if (in_array($method, ['efectivo', 'handcash', ''], true) && ($matchesPrevTransfer || $mentionsTransfer)) {
                            $auditTag = '[Ajuste auditoría: transferencia no afecta efectivo de caja]';
                            $expFlow->payment_method = 'transferencia';
                            $expFlow->cash_amount = 0.00;
                            $expFlow->transfer_amount = $amt;
                            if (!str_contains((string) $expFlow->description, $auditTag)) {
                                $expFlow->description = trim($expFlow->description . ' ' . $auditTag);
                            }
                            $expFlow->save();
                            $reclassifiedFlowsCount++;
                        }
                    }

                    // Recargar flujos del día y recalcular cortes existentes en esta fecha
                    $dayFlows = CashFlow::where('branch_id', $branch->id)
                        ->whereDate('created_at', $dateStr)
                        ->orderBy('id')
                        ->get();

                    $incomes = $dayFlows->where('type', 'income');
                    $expenses = $dayFlows->where('type', 'expense');

                    $totalIncome = round((float) $incomes->sum('amount'), 2);
                    $cashIncome = round((float) $incomes->sum(fn($f) => $f->effective_cash_amount), 2);
                    $cardIncome = round((float) $incomes->sum(fn($f) => $f->effective_card_amount), 2);
                    $transferIncome = round((float) $incomes->sum(fn($f) => $f->effective_transfer_amount), 2);
                    $cashExpense = round((float) $expenses->sum(fn($f) => $f->effective_cash_amount), 2);

                    $openingBalance = DailyCut::resolveOpeningBalance($branch->id, $dateStr);
                    $expectedCash = round($openingBalance + $cashIncome - $cashExpense, 2);

                    $cutsOnDate = DailyCut::where('branch_id', $branch->id)
                        ->whereDate('date', $dateStr)
                        ->orderBy('id')
                        ->get();

                    foreach ($cutsOnDate as $cut) {
                        $oldOpening = round((float) $cut->opening_balance, 2);
                        $oldBalance = round((float) $cut->balance, 2);
                        $oldExpense = round((float) $cut->total_expense, 2);
                        $oldDeclared = $cut->declared_cash !== null ? round((float) $cut->declared_cash, 2) : null;

                        $newDeclared = $oldDeclared;
                        // Si fue un corte automático programado donde el bug anterior guardó declared_cash = cash_income
                        if (
                            $cut->cut_type === 'corte_z'
                            && $cut->cut_mode === 'programado'
                            && $oldDeclared !== null
                            && abs($oldDeclared - $cashIncome) < 0.01
                            && abs($expectedCash - $cashIncome) >= 0.01
                        ) {
                            $newDeclared = $expectedCash;
                        }

                        $newDiff = $newDeclared !== null ? round($newDeclared - $expectedCash, 2) : 0.0;

                        $changed = abs($oldOpening - $openingBalance) >= 0.01
                            || abs($oldBalance - $expectedCash) >= 0.01
                            || abs($oldExpense - $cashExpense) >= 0.01
                            || ($oldDeclared !== $newDeclared);

                        if ($changed) {
                            $auditNote = '[Recalculado: arrastre y saldo esperado solo con efectivo]';
                            $notes = (string) $cut->notes;
                            if (!str_contains($notes, $auditNote)) {
                                $notes = trim($notes ? "{$notes} | {$auditNote}" : $auditNote);
                            }

                            $cut->opening_balance = $openingBalance;
                            $cut->total_income = $totalIncome;
                            $cut->cash_income = $cashIncome;
                            $cut->card_income = $cardIncome;
                            $cut->transfer_income = $transferIncome;
                            $cut->total_expense = $cashExpense;
                            $cut->balance = $expectedCash;
                            $cut->declared_cash = $newDeclared;
                            $cut->difference_amount = $newDiff;
                            $cut->notes = $notes;
                            $cut->save();
                        }

                        $rowsSummary[] = [
                            $branch->name,
                            $dateStr,
                            strtoupper($cut->cut_type),
                            '$' . number_format($oldOpening, 2) . ' -> $' . number_format($openingBalance, 2),
                            '$' . number_format($cashIncome, 2),
                            '$' . number_format($oldExpense, 2) . ' -> $' . number_format($cashExpense, 2),
                            '$' . number_format($oldBalance, 2) . ' -> $' . number_format($expectedCash, 2),
                            $newDeclared !== null ? '$' . number_format($newDeclared, 2) : 'N/A',
                            $changed ? 'CORREGIDO' : 'SIN CAMBIO',
                        ];
                    }
                }
            }
        };

        if ($dryRun) {
            DB::beginTransaction();
            try {
                $runner();
            } finally {
                DB::rollBack();
            }
        } else {
            DB::transaction($runner);
        }

        if (!empty($rowsSummary)) {
            $this->table(
                ['Sucursal', 'Fecha', 'Tipo', 'Saldo Inicial', 'Ing. Efectivo', 'Egr. Efectivo', 'Efectivo Esperado', 'Efectivo Declarado', 'Estado'],
                $rowsSummary
            );
        } else {
            $this->info("No se encontraron registros de cortes entre {$fromDate} y {$toDate}.");
        }

        $this->info("Egresos por transferencia reclasificados sin borrar historial: {$reclassifiedFlowsCount}");
        $this->info($dryRun ? 'Simulación finalizada (sin cambios en BD).' : 'Recálculo completado exitosamente.');

        return self::SUCCESS;
    }
}

