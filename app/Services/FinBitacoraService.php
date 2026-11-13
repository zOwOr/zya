<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\FinFinanciera;
use App\Models\FinSale;
use App\Models\FinSalePayment;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class FinBitacoraService
{
    public static function canAccessAllBranches(): bool
    {
        return auth()->check() && (
            auth()->user()->isSuperAdmin()
            || auth()->user()->can('financieras.inventario.all_branches')
            || auth()->user()->can('financieras.all_branches')
            || auth()->user()->can('dashboard.all_branches')
        );
    }

    public static function build(Request $request, array $options = []): array
    {
        $prefix = (string) ($options['prefix'] ?? '');
        $paginate = (bool) ($options['paginate'] ?? true);
        $perPage = (int) ($options['per_page'] ?? 15);
        $pageName = $prefix !== '' ? ($prefix . 'page') : 'page';

        $dateFilter = DateFilterService::resolve($request, [
            'prefix' => $prefix,
            'default_type' => $options['default_type'] ?? 'dia',
            'default_date' => $options['default_date'] ?? null,
            'default_start_date' => $options['default_start_date'] ?? null,
            'default_end_date' => $options['default_end_date'] ?? null,
            'history_permissions' => $options['history_permissions'] ?? ['financieras.filter_all_dates'],
            'fallback_unprefixed' => (bool) ($options['fallback_unprefixed'] ?? false),
        ]);

        $canAllBranches = self::canAccessAllBranches();
        $branchId = $canAllBranches
            ? ($request->filled($prefix . 'branch_id') ? (int) $request->input($prefix . 'branch_id') : null)
            : (int) (auth()->user()?->branch_id ?? 1);

        $movementType = (string) $request->input($prefix . 'movement_type', 'todos');
        if (!in_array($movementType, ['todos', 'abono', 'enganche'], true)) {
            $movementType = 'todos';
        }

        $financieraId = $request->filled($prefix . 'financiera_id')
            ? (int) $request->input($prefix . 'financiera_id')
            : null;
        $search = trim((string) $request->input($prefix . 'search', ''));

        $rows = collect();

        if (in_array($movementType, ['todos', 'abono'], true)) {
            $abonoQuery = FinSalePayment::with([
                'sale.financiera',
                'financiera',
                'finSupplier',
                'branch',
                'user',
                'posTransaction',
                'externalCustomer',
            ])
                ->where(function ($q) {
                    $q->whereNull('pos_transaction_id')
                        ->orWhereHas('posTransaction', fn ($pt) => $pt->where('status', '!=', 'cancelado'));
                });

            DateFilterService::apply($abonoQuery, 'payment_date', $dateFilter);

            $abonoQuery
                ->when($financieraId, fn ($q) => $q->where('financiera_id', $financieraId))
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('receipt_code', 'like', "%{$search}%")
                            ->orWhere('tag_contrato', 'like', "%{$search}%")
                            ->orWhere('imei', 'like', "%{$search}%")
                            ->orWhere('customer_name', 'like', "%{$search}%")
                            ->orWhereHas('externalCustomer', function ($ext) use ($search) {
                                $ext->where('tit_name', 'like', "%{$search}%")
                                    ->orWhere('tit_phone', 'like', "%{$search}%");
                            });
                    });
                });

            foreach ($abonoQuery->get() as $p) {
                $folio = $p->posTransaction?->transaction_code ?: ($p->receipt_code ?: ('AB-' . $p->id));
                $finName = $p->financiera?->name ?? ($p->finSupplier?->name ?? ($p->sale?->financiera?->name ?? 'Financiera'));
                $customerName = $p->customer_name ?? ($p->externalCustomer?->tit_name ?? ($p->sale?->customer_name ?? 'Cliente'));

                $rows->push([
                    'id' => 'abono-' . $p->id,
                    'sort_at' => optional($p->payment_date)->timestamp ?? 0,
                    'sort_id' => (int) $p->id,
                    'date_formatted' => optional($p->payment_date)->format('d/m/Y H:i') ?? '-',
                    'movement_type' => 'abono',
                    'movement_label' => $p->is_external_company
                        ? 'Abono (Ext.)'
                        : ($p->installment_number ? "Abono #{$p->installment_number}" : 'Abono'),
                    'folio' => $folio,
                    'tag_contrato' => $p->tag_contrato ?: '-',
                    'imei' => $p->imei ?: '-',
                    'customer_name' => $customerName,
                    'is_external_customer' => !empty($p->external_customer_id),
                    'is_external_company' => (bool) $p->is_external_company,
                    'financiera_name' => $finName,
                    'branch_name' => $p->branch?->name ?? 'General',
                    'payment_method' => strtoupper((string) ($p->payment_method ?: 'efectivo')),
                    'amount' => round((float) $p->amount, 2),
                    'commission' => round((float) $p->commission_amount, 2),
                    'total' => round((float) $p->total_paid, 2),
                    'cash_amount' => round((float) $p->cash_amount, 2),
                    'card_amount' => round((float) $p->card_amount, 2),
                    'transfer_amount' => round((float) $p->transfer_amount, 2),
                    'user_name' => $p->user?->name ?? 'Sistema',
                    'fin_sale_id' => $p->fin_sale_id,
                    'notes' => (string) ($p->notes ?? ''),
                ]);
            }
        }

        if (in_array($movementType, ['todos', 'enganche'], true)) {
            $engancheQuery = FinSale::with(['device.brand', 'financiera', 'branch', 'seller'])
                ->where('status', '!=', 'cancelada')
                ->where(function ($q) {
                    $q->whereNull('sale_type')->orWhere('sale_type', '!=', 'contado');
                });

            DateFilterService::apply($engancheQuery, 'sale_date', $dateFilter);

            $engancheQuery
                ->when($financieraId, fn ($q) => $q->where('financiera_id', $financieraId))
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('sale_code', 'like', "%{$search}%")
                            ->orWhere('tag_contrato', 'like', "%{$search}%")
                            ->orWhere('customer_name', 'like', "%{$search}%")
                            ->orWhere('customer_phone', 'like', "%{$search}%")
                            ->orWhereHas('device', function ($d) use ($search) {
                                $d->where('imei', 'like', "%{$search}%")
                                    ->orWhere('model', 'like', "%{$search}%");
                            });
                    });
                });

            foreach ($engancheQuery->get() as $s) {
                $hasDiscount = !is_null($s->enganche_descuento) && $s->enganche_descuento !== '';
                $effectiveEnganche = $hasDiscount ? (float) $s->enganche_descuento : (float) $s->down_payment;
                if ($effectiveEnganche <= 0) {
                    continue;
                }

                $rows->push([
                    'id' => 'enganche-' . $s->id,
                    'sort_at' => optional($s->sale_date)->timestamp ?? 0,
                    'sort_id' => (int) $s->id,
                    'date_formatted' => optional($s->sale_date)->format('d/m/Y H:i') ?? '-',
                    'movement_type' => 'enganche',
                    'movement_label' => ($hasDiscount && (float) $s->enganche_descuento !== (float) $s->down_payment)
                        ? 'Enganche (c/ desc.)'
                        : 'Enganche',
                    'folio' => $s->sale_code ?: ('FIN-' . $s->id),
                    'tag_contrato' => $s->tag_contrato ?: '-',
                    'imei' => $s->device?->imei ?: '-',
                    'customer_name' => $s->customer_name ?: 'Cliente',
                    'is_external_customer' => false,
                    'is_external_company' => false,
                    'financiera_name' => $s->financiera?->name ?? 'Directo',
                    'branch_name' => $s->branch?->name ?? 'General',
                    'payment_method' => strtoupper((string) ($s->payment_method ?: 'ENGANCHE VENTA')),
                    'amount' => round($effectiveEnganche, 2),
                    'commission' => 0.0,
                    'total' => round($effectiveEnganche, 2),
                    'cash_amount' => 0.0,
                    'card_amount' => 0.0,
                    'transfer_amount' => 0.0,
                    'user_name' => $s->seller_display_name !== 'N/A' ? $s->seller_display_name : 'Sistema',
                    'fin_sale_id' => $s->id,
                    'notes' => 'Precio equipo: $' . number_format((float) $s->price, 2),
                ]);
            }
        }

        $sorted = $rows
            ->sort(function ($a, $b) {
                if ($a['sort_at'] === $b['sort_at']) {
                    return $b['sort_id'] <=> $a['sort_id'];
                }
                return $b['sort_at'] <=> $a['sort_at'];
            })
            ->values();

        $abonos = $sorted->where('movement_type', 'abono');
        $enganches = $sorted->where('movement_type', 'enganche');

        $totals = [
            'count' => $sorted->count(),
            'abonos_count' => $abonos->count(),
            'abonos_amount' => round((float) $abonos->sum('amount'), 2),
            'abonos_commission' => round((float) $abonos->sum('commission'), 2),
            'abonos_total' => round((float) $abonos->sum('total'), 2),
            'enganches_count' => $enganches->count(),
            'enganches_total' => round((float) $enganches->sum('total'), 2),
            'grand_total' => round((float) $sorted->sum('total'), 2),
        ];

        $groupedByFinanciera = $sorted
            ->groupBy('financiera_name')
            ->sortKeys()
            ->map(function ($items, $name) {
                $grpAbonos = $items->where('movement_type', 'abono');
                $grpEnganches = $items->where('movement_type', 'enganche');
                return [
                    'name' => $name,
                    'count' => $items->count(),
                    'abonos_count' => $grpAbonos->count(),
                    'abonos_total' => round((float) $grpAbonos->sum('total'), 2),
                    'enganches_count' => $grpEnganches->count(),
                    'enganches_total' => round((float) $grpEnganches->sum('total'), 2),
                    'total' => round((float) $items->sum('total'), 2),
                ];
            })
            ->values();

        if ($paginate) {
            $currentPage = LengthAwarePaginator::resolveCurrentPage($pageName);
            $sliced = $sorted->forPage($currentPage, $perPage)->values();
            $entries = new LengthAwarePaginator(
                $sliced,
                $sorted->count(),
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'pageName' => $pageName,
                ]
            );
            $entries->appends($request->query());
        } else {
            $entries = $sorted;
        }

        $branches = Branch::orderBy('name')->get(['id', 'name']);
        $financieras = FinFinanciera::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        if ($financieras->isEmpty()) {
            $financieras = FinFinanciera::orderBy('name')->get(['id', 'name']);
        }
        $branchName = $branchId ? ($branches->firstWhere('id', $branchId)?->name ?? 'Sucursal') : 'Todas las Sucursales';

        return [
            'entries' => $entries,
            'allEntries' => $sorted,
            'totals' => $totals,
            'groupedByFinanciera' => $groupedByFinanciera,
            'dateFilter' => $dateFilter,
            'periodLabel' => DateFilterService::periodLabel($dateFilter),
            'fileSuffix' => DateFilterService::fileSuffix($dateFilter),
            'movementType' => $movementType,
            'financieraId' => $financieraId,
            'branchId' => $branchId,
            'branchName' => $branchName,
            'canAllBranches' => $canAllBranches,
            'search' => $search,
            'prefix' => $prefix,
            'branches' => $branches,
            'financieras' => $financieras,
        ];
    }

    public static function buildSpreadsheet(array $data): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bitacora Financieras');

        $headers = [
            'Fecha / Hora',
            'Tipo Movimiento',
            'Folio / Recibo',
            'Financiera',
            'TAG / Contrato',
            'IMEI',
            'Cliente',
            'Sucursal',
            'Método Pago',
            'Capital / Abono / Enganche ($)',
            'Comisión ($)',
            'Total ($)',
            'Efectivo ($)',
            'Tarjeta ($)',
            'Transferencia ($)',
            'Usuario / Vendedor',
            'Notas',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:Q1')->getFont()->setBold(true);

        $row = 2;
        foreach ($data['allEntries'] as $item) {
            $sheet->fromArray([
                $item['date_formatted'],
                $item['movement_label'],
                $item['folio'],
                $item['financiera_name'],
                $item['tag_contrato'],
                '',
                $item['customer_name'] . (!empty($item['is_external_customer']) ? ' (Externo)' : ''),
                $item['branch_name'],
                $item['payment_method'],
                (float) $item['amount'],
                (float) $item['commission'],
                (float) $item['total'],
                (float) $item['cash_amount'],
                (float) $item['card_amount'],
                (float) $item['transfer_amount'],
                $item['user_name'],
                $item['notes'],
            ], null, "A{$row}");
            $sheet->setCellValueExplicit("F{$row}", (string) $item['imei'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $row++;
        }

        $totals = $data['totals'];
        $sheet->fromArray([
            '',
            'TOTALES',
            $totals['count'] . ' movs',
            '',
            '',
            '',
            '',
            '',
            '',
            round($totals['abonos_amount'] + $totals['enganches_total'], 2),
            $totals['abonos_commission'],
            $totals['grand_total'],
            '',
            '',
            '',
            '',
            '',
        ], null, "A{$row}");
        $sheet->getStyle("B{$row}:L{$row}")->getFont()->setBold(true);

        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
