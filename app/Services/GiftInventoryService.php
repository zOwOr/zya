<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Gift;
use App\Models\GiftMovement;
use App\Models\GiftStock;
use App\Models\GiftTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GiftInventoryService
{
    public static function cleanName(?string $name): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $name)) ?? trim((string) $name);
    }

    public static function generateUniqueCode(string $prefix = 'GFT'): string
    {
        do {
            $code = $prefix . '-' . strtoupper(Str::random(6));
        } while (Gift::where('code', $code)->exists());

        return $code;
    }

    public static function findDuplicateByName(string $name, ?int $ignoreGiftId = null): ?Gift
    {
        $normalized = Gift::normalizeName($name);
        if ($normalized === '') {
            return null;
        }

        $query = Gift::query();
        if ($ignoreGiftId !== null) {
            $query->where('id', '!=', $ignoreGiftId);
        }

        if (Schema::hasColumn('gifts', 'normalized_name')) {
            $match = (clone $query)->where('normalized_name', $normalized)->first();
            if ($match) {
                return $match;
            }
        }

        return $query->get()->first(function (Gift $gift) use ($normalized) {
            return Gift::normalizeName($gift->name) === $normalized;
        });
    }

    /**
     * Registra un regalo nuevo en el Catálogo Maestro Único (Inventario General, branch_id = null)
     * rechazando duplicados por nombre normalizado.
     */
    public static function createMasterGift(array $data, ?int $userId = null): Gift
    {
        return DB::transaction(function () use ($data, $userId) {
            $cleanName = static::cleanName($data['name'] ?? '');
            if ($cleanName === '') {
                throw ValidationException::withMessages([
                    'name' => 'El nombre del regalo es obligatorio.',
                ]);
            }

            $duplicate = static::findDuplicateByName($cleanName);
            if ($duplicate) {
                throw ValidationException::withMessages([
                    'name' => "Ya existe el regalo '{$duplicate->name}' ({$duplicate->code}) en el Catálogo Maestro. Usa 'Entrada a Inventario General' o 'Traspasar a Sucursal' en lugar de crear un duplicado.",
                ]);
            }

            $code = trim((string) ($data['code'] ?? ''));
            if ($code !== '') {
                if (Gift::where('code', $code)->exists()) {
                    throw ValidationException::withMessages([
                        'code' => "El código '{$code}' ya está registrado en el Catálogo Maestro.",
                    ]);
                }
            } else {
                $code = static::generateUniqueCode();
            }

            $initialStock = max(0, (int) ($data['stock_quantity'] ?? 0));

            $gift = Gift::create([
                'code' => $code,
                'name' => $cleanName,
                'normalized_name' => Gift::normalizeName($cleanName),
                'brand' => filled($data['brand'] ?? null) ? trim((string) $data['brand']) : null,
                'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
                'supplier_id' => !empty($data['supplier_id']) ? (int) $data['supplier_id'] : null,
                'fin_supplier_id' => !empty($data['fin_supplier_id']) ? (int) $data['fin_supplier_id'] : null,
                'supplier_name' => filled($data['supplier_name'] ?? null) ? trim((string) $data['supplier_name']) : null,
                'branch_id' => null,
                'stock_quantity' => $initialStock,
                'min_stock' => max(0, (int) ($data['min_stock'] ?? 3)),
                'unit_cost' => max(0, (float) ($data['unit_cost'] ?? 0)),
                'status' => $data['status'] ?? 'activo',
            ]);

            if ($initialStock > 0) {
                GiftMovement::create([
                    'gift_id' => $gift->id,
                    'branch_id' => null,
                    'user_id' => $userId,
                    'movement_type' => 'entrada_proveedor',
                    'quantity' => $initialStock,
                    'previous_stock' => 0,
                    'new_stock' => $initialStock,
                    'supplier_name' => $gift->supplier_display_name,
                    'reference_module' => 'gifts',
                    'reference_code' => $gift->code,
                    'notes' => trim((string) ($data['notes'] ?? '')) ?: 'Stock inicial al registrar regalo en Inventario General (Catálogo Maestro)',
                    'movement_date' => now(),
                ]);
            }

            return $gift->fresh(['supplier', 'finSupplier', 'stocks.branch']);
        });
    }

    /**
     * Registra una entrada/recepción de piezas exclusivamente en el Inventario General (almacén matriz).
     *
     * @return array{gift: Gift, movement: GiftMovement, previous_stock: int, new_stock: int}
     */
    public static function addCentralEntry(int $giftId, int $quantity, array $meta = [], ?int $userId = null): array
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'La cantidad de entrada debe ser mayor o igual a 1.',
            ]);
        }

        return DB::transaction(function () use ($giftId, $quantity, $meta, $userId) {
            /** @var Gift|null $gift */
            $gift = Gift::where('id', $giftId)->lockForUpdate()->first();
            if (!$gift) {
                throw ValidationException::withMessages([
                    'gift_id' => 'El regalo seleccionado no existe en el Catálogo Maestro.',
                ]);
            }

            if ($gift->status !== 'activo') {
                throw ValidationException::withMessages([
                    'gift_id' => "El regalo '{$gift->name}' está inactivo. Actívalo antes de recibir entradas.",
                ]);
            }

            $prevStock = (int) $gift->stock_quantity;
            $newStock = $prevStock + $quantity;

            $gift->stock_quantity = $newStock;
            if (array_key_exists('unit_cost', $meta) && $meta['unit_cost'] !== null && $meta['unit_cost'] !== '') {
                $gift->unit_cost = max(0, (float) $meta['unit_cost']);
            }
            $gift->save();

            $movement = GiftMovement::create([
                'gift_id' => $gift->id,
                'branch_id' => null,
                'user_id' => $userId,
                'movement_type' => 'entrada_proveedor',
                'quantity' => $quantity,
                'previous_stock' => $prevStock,
                'new_stock' => $newStock,
                'supplier_name' => $gift->supplier_display_name,
                'reference_module' => $meta['reference_module'] ?? 'gifts',
                'reference_code' => $meta['reference_code'] ?? $gift->code,
                'notes' => trim((string) ($meta['notes'] ?? '')) ?: 'Entrada de stock al Inventario General (Almacén Central)',
                'movement_date' => $meta['movement_date'] ?? now(),
            ]);

            return [
                'gift' => $gift,
                'movement' => $movement,
                'previous_stock' => $prevStock,
                'new_stock' => $newStock,
            ];
        });
    }

    /**
     * Realiza un traspaso atómico desde Inventario General (from_branch_id = null) o desde una sucursal origen
     * hacia una sucursal destino, conservando el mismo gift_id del Catálogo Maestro.
     */
    public static function transfer(
        int $giftId,
        ?int $fromBranchId,
        int $toBranchId,
        int $quantity,
        ?string $notes = null,
        ?int $userId = null
    ): GiftTransfer {
        $fromBranchId = !empty($fromBranchId) ? (int) $fromBranchId : null;

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'La cantidad a traspasar debe ser al menos 1 pieza.',
            ]);
        }

        if ($toBranchId <= 0) {
            throw ValidationException::withMessages([
                'to_branch_id' => 'Debes seleccionar una sucursal destino válida.',
            ]);
        }

        if ($fromBranchId !== null && $fromBranchId === $toBranchId) {
            throw ValidationException::withMessages([
                'to_branch_id' => 'La sucursal destino debe ser diferente al origen del traspaso.',
            ]);
        }

        return DB::transaction(function () use ($giftId, $fromBranchId, $toBranchId, $quantity, $notes, $userId) {
            // Bloquear fila maestra primero serializa cualquier operación concurrente sobre el mismo regalo.
            /** @var Gift|null $gift */
            $gift = Gift::where('id', $giftId)->lockForUpdate()->first();
            if (!$gift) {
                throw ValidationException::withMessages([
                    'gift_id' => 'El regalo seleccionado no existe en el Catálogo Maestro.',
                ]);
            }

            if ($gift->status !== 'activo') {
                throw ValidationException::withMessages([
                    'gift_id' => "El regalo '{$gift->name}' está inactivo y no puede traspasarse.",
                ]);
            }

            $toBranch = Branch::find($toBranchId);
            if (!$toBranch) {
                throw ValidationException::withMessages([
                    'to_branch_id' => 'La sucursal destino seleccionada no existe.',
                ]);
            }

            $fromBranch = null;
            if ($fromBranchId !== null) {
                $fromBranch = Branch::find($fromBranchId);
                if (!$fromBranch) {
                    throw ValidationException::withMessages([
                        'from_branch_id' => 'La sucursal origen seleccionada no existe.',
                    ]);
                }
            }

            $originLabel = $fromBranch ? $fromBranch->name : 'Inventario General (Almacén Central)';

            if ($fromBranchId === null) {
                $fromPrev = (int) $gift->stock_quantity;
                if ($fromPrev < $quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => "Stock insuficiente en {$originLabel} para '{$gift->name}' (Disponible: {$fromPrev}, Solicitado: {$quantity}).",
                    ]);
                }

                $fromNew = $fromPrev - $quantity;
                $gift->stock_quantity = $fromNew;
                $gift->save();
            } else {
                $fromStock = GiftStock::where('gift_id', $gift->id)
                    ->where('branch_id', $fromBranchId)
                    ->lockForUpdate()
                    ->first();

                $fromPrev = (int) ($fromStock?->stock_quantity ?? 0);
                if ($fromPrev < $quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => "Stock insuficiente en {$originLabel} para '{$gift->name}' (Disponible: {$fromPrev}, Solicitado: {$quantity}).",
                    ]);
                }

                $fromNew = $fromPrev - $quantity;
                $fromStock->stock_quantity = $fromNew;
                $fromStock->save();
            }

            $toStock = GiftStock::where('gift_id', $gift->id)
                ->where('branch_id', $toBranchId)
                ->lockForUpdate()
                ->first();

            if (!$toStock) {
                $toStock = GiftStock::create([
                    'gift_id' => $gift->id,
                    'branch_id' => $toBranchId,
                    'stock_quantity' => 0,
                    'min_stock' => max(0, (int) $gift->min_stock),
                ]);
            }

            $toPrev = (int) $toStock->stock_quantity;
            $toNew = $toPrev + $quantity;
            $toStock->stock_quantity = $toNew;
            $toStock->save();

            do {
                $transferCode = 'TRF-GFT-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
            } while (GiftTransfer::where('transfer_code', $transferCode)->exists());

            $cleanNotes = trim((string) $notes);
            $now = now();

            $transfer = GiftTransfer::create([
                'transfer_code' => $transferCode,
                'gift_id' => $gift->id,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'quantity' => $quantity,
                'from_previous_stock' => $fromPrev,
                'from_new_stock' => $fromNew,
                'to_previous_stock' => $toPrev,
                'to_new_stock' => $toNew,
                'user_id' => $userId,
                'notes' => $cleanNotes !== '' ? $cleanNotes : "Traspaso de {$originLabel} hacia {$toBranch->name}",
                'transferred_at' => $now,
            ]);

            GiftMovement::create([
                'gift_id' => $gift->id,
                'gift_transfer_id' => $transfer->id,
                'branch_id' => $fromBranchId,
                'user_id' => $userId,
                'movement_type' => 'traspaso_salida',
                'quantity' => $quantity,
                'previous_stock' => $fromPrev,
                'new_stock' => $fromNew,
                'supplier_name' => $gift->supplier_display_name,
                'reference_module' => 'gift_transfers',
                'reference_code' => $transferCode,
                'notes' => ($cleanNotes !== '' ? $cleanNotes . ' | ' : '') . "Salida por traspaso ({$transferCode}) hacia {$toBranch->name}",
                'movement_date' => $now,
            ]);

            GiftMovement::create([
                'gift_id' => $gift->id,
                'gift_transfer_id' => $transfer->id,
                'branch_id' => $toBranchId,
                'user_id' => $userId,
                'movement_type' => 'traspaso_entrada',
                'quantity' => $quantity,
                'previous_stock' => $toPrev,
                'new_stock' => $toNew,
                'supplier_name' => $gift->supplier_display_name,
                'reference_module' => 'gift_transfers',
                'reference_code' => $transferCode,
                'notes' => ($cleanNotes !== '' ? $cleanNotes . ' | ' : '') . "Entrada por traspaso ({$transferCode}) desde {$originLabel}",
                'movement_date' => $now,
            ]);

            return $transfer->load(['gift', 'fromBranch', 'toBranch', 'user']);
        });
    }

    /**
     * Descuenta exclusivamente el stock asignado a la sucursal especificada al entregar un regalo al cliente.
     */
    public static function deliverFromBranch(
        int $giftId,
        int $branchId,
        int $quantity,
        array $meta = [],
        ?int $userId = null
    ): GiftMovement {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'La cantidad a entregar debe ser al menos 1 pieza.',
            ]);
        }

        if ($branchId <= 0) {
            throw ValidationException::withMessages([
                'branch_id' => 'La entrega de regalos solo puede realizarse desde una sucursal válida.',
            ]);
        }

        return DB::transaction(function () use ($giftId, $branchId, $quantity, $meta, $userId) {
            /** @var Gift|null $gift */
            $gift = Gift::where('id', $giftId)->lockForUpdate()->first();
            if (!$gift || $gift->status !== 'activo') {
                throw ValidationException::withMessages([
                    'gift_id' => 'El regalo seleccionado no existe o no está activo.',
                ]);
            }

            $branch = Branch::find($branchId);
            $branchName = $branch ? $branch->name : "Sucursal #{$branchId}";

            $branchStock = GiftStock::where('gift_id', $gift->id)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            $prevStock = (int) ($branchStock?->stock_quantity ?? 0);
            if ($prevStock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Stock insuficiente del regalo '{$gift->name}' en {$branchName} (Disponible en sucursal: {$prevStock}, Solicitado: {$quantity}).",
                ]);
            }

            $newStock = $prevStock - $quantity;
            $branchStock->stock_quantity = $newStock;
            $branchStock->save();

            return GiftMovement::create([
                'gift_id' => $gift->id,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'movement_type' => 'entrega_cliente',
                'quantity' => $quantity,
                'previous_stock' => $prevStock,
                'new_stock' => $newStock,
                'supplier_name' => $gift->supplier_display_name,
                'customer_name' => $meta['customer_name'] ?? 'Cliente General',
                'customer_phone' => $meta['customer_phone'] ?? null,
                'reference_module' => $meta['reference_module'] ?? 'gifts',
                'reference_code' => $meta['reference_code'] ?? $gift->code,
                'notes' => trim((string) ($meta['notes'] ?? '')) ?: "Entrega de regalo a cliente en {$branchName}",
                'movement_date' => $meta['movement_date'] ?? now(),
            ]);
        });
    }

    /**
     * Consolida registros históricos duplicados de `gifts` por nombre normalizado sin perder stock por sucursal
     * ni historial de movimientos/traspasos. Es idempotente y segura ante ejecuciones repetidas.
     *
     * @return array{groups: int, masters_kept: int, duplicates_merged: int, branch_stocks_synced: int}
     */
    public static function consolidateExistingGifts(): array
    {
        return DB::transaction(function () {
            $hasStocksTable = Schema::hasTable('gift_stocks');
            $hasTransfersTable = Schema::hasTable('gift_transfers');
            $hasNormalizedColumn = Schema::hasColumn('gifts', 'normalized_name');

            $validBranchIds = Branch::pluck('id')->map(fn ($id) => (int) $id)->all();

            $allGifts = Gift::query()
                ->orderByRaw('CASE WHEN branch_id IS NULL THEN 0 ELSE 1 END')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($allGifts->isEmpty()) {
                return [
                    'groups' => 0,
                    'masters_kept' => 0,
                    'duplicates_merged' => 0,
                    'branch_stocks_synced' => 0,
                ];
            }

            $groups = $allGifts->groupBy(function (Gift $gift) {
                $norm = Gift::normalizeName($gift->name);
                return $norm !== '' ? $norm : ('__gift_' . $gift->id);
            });

            $mastersKept = 0;
            $duplicatesMerged = 0;
            $branchStocksSynced = 0;

            foreach ($groups as $normalizedKey => $group) {
                /** @var Gift $master */
                $master = $group->first();
                $groupIds = $group->pluck('id')->all();

                $centralStock = 0;
                $branchStocks = [];
                $branchMinStocks = [];

                // 1. Conservar stocks ya registrados en gift_stocks (idempotencia si la función se re-ejecuta)
                if ($hasStocksTable) {
                    $existingStocks = GiftStock::whereIn('gift_id', $groupIds)->lockForUpdate()->get();
                    foreach ($existingStocks as $gs) {
                        $bId = (int) $gs->branch_id;
                        $branchStocks[$bId] = ($branchStocks[$bId] ?? 0) + max(0, (int) $gs->stock_quantity);
                        $branchMinStocks[$bId] = max($branchMinStocks[$bId] ?? 0, max(0, (int) $gs->min_stock));
                    }
                }

                $bestBrand = $master->brand;
                $bestDescription = $master->description;
                $bestSupplierId = $master->supplier_id;
                $bestFinSupplierId = $master->fin_supplier_id;
                $bestSupplierName = $master->supplier_name;
                $bestUnitCost = (float) $master->unit_cost;
                $bestMinStock = max(0, (int) $master->min_stock);
                $bestStatus = $master->status ?: 'inactivo';

                // 2. Acumular existencias heredadas desde filas de `gifts` (central vs por sucursal) y metadatos
                foreach ($group as $row) {
                    if ($bestBrand === null && filled($row->brand)) {
                        $bestBrand = $row->brand;
                    }
                    if ($bestDescription === null && filled($row->description)) {
                        $bestDescription = $row->description;
                    }
                    if ($bestSupplierId === null && !empty($row->supplier_id)) {
                        $bestSupplierId = $row->supplier_id;
                    }
                    if ($bestFinSupplierId === null && !empty($row->fin_supplier_id)) {
                        $bestFinSupplierId = $row->fin_supplier_id;
                    }
                    if ($bestSupplierName === null && filled($row->supplier_name)) {
                        $bestSupplierName = $row->supplier_name;
                    }
                    if ($bestUnitCost <= 0 && (float) $row->unit_cost > 0) {
                        $bestUnitCost = (float) $row->unit_cost;
                    }
                    if ((int) $row->min_stock > 0 && ($bestMinStock === 0 || (int) $row->min_stock < $bestMinStock)) {
                        $bestMinStock = (int) $row->min_stock;
                    }
                    if ($row->status === 'activo') {
                        $bestStatus = 'activo';
                    }

                    $qty = max(0, (int) $row->stock_quantity);
                    if (empty($row->branch_id)) {
                        $centralStock += $qty;
                    } else {
                        $bId = (int) $row->branch_id;
                        if (in_array($bId, $validBranchIds, true)) {
                            $branchStocks[$bId] = ($branchStocks[$bId] ?? 0) + $qty;
                            $branchMinStocks[$bId] = max($branchMinStocks[$bId] ?? 0, max(0, (int) $row->min_stock));
                        } else {
                            // Resguardo en caso de sucursal eliminada manualmente: conservar en Inventario General
                            $centralStock += $qty;
                        }
                    }

                    // Preservar branch_id original en movimientos previos que lo tuvieran null
                    if (!empty($row->branch_id)) {
                        GiftMovement::where('gift_id', $row->id)
                            ->whereNull('branch_id')
                            ->update(['branch_id' => (int) $row->branch_id]);
                    }
                }

                // 3. Reasignar movimientos/traspasos de duplicados hacia el registro maestro y eliminar duplicados
                foreach ($group as $row) {
                    if ($row->id === $master->id) {
                        continue;
                    }

                    GiftMovement::where('gift_id', $row->id)->update(['gift_id' => $master->id]);

                    if ($hasTransfersTable) {
                        GiftTransfer::where('gift_id', $row->id)->update(['gift_id' => $master->id]);
                    }

                    if ($hasStocksTable) {
                        GiftStock::where('gift_id', $row->id)->delete();
                    }

                    $row->delete();
                    $duplicatesMerged++;
                }

                // 4. Actualizar fila maestra como Inventario General (branch_id = null)
                $cleanName = static::cleanName($master->name) ?: $master->code;
                $master->name = $cleanName;
                if ($hasNormalizedColumn) {
                    $master->normalized_name = str_starts_with((string) $normalizedKey, '__gift_')
                        ? Gift::normalizeName($cleanName)
                        : (string) $normalizedKey;
                }
                $master->code = filled($master->code) ? trim((string) $master->code) : static::generateUniqueCode();
                $master->brand = $bestBrand;
                $master->description = $bestDescription;
                $master->supplier_id = $bestSupplierId;
                $master->fin_supplier_id = $bestFinSupplierId;
                $master->supplier_name = $bestSupplierName;
                $master->branch_id = null;
                $master->stock_quantity = $centralStock;
                $master->min_stock = $bestMinStock > 0 ? $bestMinStock : 3;
                $master->unit_cost = $bestUnitCost;
                $master->status = $bestStatus ?: 'activo';
                $master->save();
                $mastersKept++;

                // 5. Sincronizar stocks por sucursal en gift_stocks
                if ($hasStocksTable) {
                    foreach ($branchStocks as $bId => $qty) {
                        GiftStock::updateOrCreate(
                            ['gift_id' => $master->id, 'branch_id' => (int) $bId],
                            [
                                'stock_quantity' => max(0, (int) $qty),
                                'min_stock' => $branchMinStocks[$bId] ?? $master->min_stock,
                            ]
                        );
                        $branchStocksSynced++;
                    }
                }
            }

            return [
                'groups' => $groups->count(),
                'masters_kept' => $mastersKept,
                'duplicates_merged' => $duplicatesMerged,
                'branch_stocks_synced' => $branchStocksSynced,
            ];
        });
    }
}

