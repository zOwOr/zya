<?php

namespace App\Services;

use App\Models\FinFinanciera;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FinancieraFilterService
{
    public const PERMISSION_FILTER_MULTIPLE = 'financieras.filter_multiple';

    /**
     * Determina si el usuario autenticado (o provisto) tiene permiso exclusivo para
     * seleccionar múltiples financieras en los reportes de abonos y ventas.
     */
    public static function userCanFilterMultiple(?Authenticatable $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user || !method_exists($user, 'can')) {
            return false;
        }

        return (bool) $user->can(self::PERMISSION_FILTER_MULTIPLE);
    }

    /**
     * Extrae el valor crudo del filtro de financieras desde el request HTTP.
     */
    public static function rawInputFromRequest(Request $request): mixed
    {
        $value = $request->input('financiera_id');
        if ($value === null || $value === '' || $value === []) {
            $alt = $request->input('financiera_ids');
            if ($alt !== null && $alt !== '' && $alt !== []) {
                return $alt;
            }
        }

        return $value;
    }

    /**
     * Normaliza un valor escalar o arreglo a una lista única de IDs enteros positivos.
     *
     * @return int[]
     */
    public static function normalizeIds(mixed $rawInput): array
    {
        if ($rawInput === null || $rawInput === '' || $rawInput === []) {
            return [];
        }

        $items = is_array($rawInput) ? $rawInput : [$rawInput];
        $normalized = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                foreach ($item as $nested) {
                    if (is_scalar($nested) && is_numeric($nested) && (int) $nested > 0) {
                        $normalized[] = (int) $nested;
                    }
                }
                continue;
            }

            if (is_scalar($item) && is_numeric($item) && (int) $item > 0) {
                $normalized[] = (int) $item;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Resuelve los IDs de financiera aplicando la restricción de permiso en el servidor.
     * Si el usuario no cuenta con el permiso exclusivo, nunca permite consolidar más
     * de una financiera (incluso si se manipula ?financiera_id[]=1&financiera_id[]=2).
     *
     * @return int[]
     */
    public static function resolveSelectedIds(
        mixed $rawInput,
        ?Authenticatable $user = null,
        ?bool $canMulti = null
    ): array {
        $ids = self::normalizeIds($rawInput);
        if (empty($ids)) {
            return [];
        }

        $allowMultiple = $canMulti ?? self::userCanFilterMultiple($user);
        if (!$allowMultiple && count($ids) > 1) {
            return [$ids[0]];
        }

        return $ids;
    }

    /**
     * Aplica el filtro de financieras (where o whereIn según permiso y cantidad) a una consulta Eloquent.
     *
     * @return int[] IDs efectivamente aplicados en la consulta.
     */
    public static function applyToQuery(
        Builder $query,
        mixed $rawInput,
        string $column = 'financiera_id',
        ?Authenticatable $user = null,
        ?bool $canMulti = null
    ): array {
        $ids = self::resolveSelectedIds($rawInput, $user, $canMulti);

        if (count($ids) === 1) {
            $query->where($column, $ids[0]);
        } elseif (count($ids) > 1) {
            $query->whereIn($column, $ids);
        }

        return $ids;
    }

    /**
     * Construye una etiqueta legible para encabezados PDF con las financieras filtradas.
     *
     * @param  int[]  $selectedIds
     */
    public static function filterLabel(array $selectedIds): string
    {
        if (empty($selectedIds)) {
            return 'Todas las Financieras';
        }

        $names = FinFinanciera::whereIn('id', $selectedIds)
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->values();

        if ($names->isEmpty()) {
            return count($selectedIds) > 1 ? 'Financieras seleccionadas' : 'Financiera seleccionada';
        }

        return $names->implode(', ');
    }
}

