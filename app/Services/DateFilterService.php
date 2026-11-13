<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

class DateFilterService
{
    public const MAX_RESTRICTED_DAYS = 2;

    /**
     * Determina si el usuario tiene permiso para consultar fechas históricas (> 2 días) y todos los registros.
     */
    public static function userCanFilterAllDates($user, string|array $permissions = []): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        foreach ((array) $permissions as $permission) {
            if (!empty($permission) && method_exists($user, 'can') && $user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normaliza el filtro de fechas (día específico, rango entre fechas o todos los registros)
     * aplicando el tope de hasta 2 días de anterioridad cuando el rol no tiene permiso histórico.
     */
    public static function resolve(Request $request, array $options = []): array
    {
        $prefix = (string) ($options['prefix'] ?? '');
        $today = ($options['today'] ?? null) instanceof Carbon
            ? $options['today']->copy()->startOfDay()
            : Carbon::today();
        $todayStr = $today->toDateString();

        $maxPastDays = max(0, (int) ($options['max_past_days'] ?? self::MAX_RESTRICTED_DAYS));
        $minAllowedDate = $today->copy()->subDays($maxPastDays)->toDateString();

        $canAllDates = array_key_exists('can_all_dates', $options) && $options['can_all_dates'] !== null
            ? (bool) $options['can_all_dates']
            : self::userCanFilterAllDates(auth()->user(), $options['history_permissions'] ?? []);

        $allowPeriodAliases = (bool) ($options['allow_period_aliases'] ?? false);
        $fallbackUnprefixed = (bool) ($options['fallback_unprefixed'] ?? false);

        $defaultType = (string) ($options['default_type'] ?? 'dia');
        if ($defaultType === 'todos' && !$canAllDates) {
            $defaultType = 'rango';
        }

        $defaultDate = self::safeDateString($options['default_date'] ?? null, $todayStr);
        $defaultStart = self::safeDateString($options['default_start_date'] ?? null, $todayStr);
        $defaultEnd = self::safeDateString($options['default_end_date'] ?? null, $todayStr);

        $rawType = $request->input($prefix . 'filter_type');
        if ($rawType === null && $allowPeriodAliases) {
            $rawType = $request->input($prefix . 'period');
        }
        if ($rawType === null && $fallbackUnprefixed && $prefix !== '') {
            $rawType = $request->input('filter_type');
        }

        $hasExplicitSingle = $request->filled($prefix . 'date')
            || ($fallbackUnprefixed && $prefix !== '' && $request->filled('date'));
        $hasExplicitRange = $request->filled($prefix . 'start_date')
            || $request->filled($prefix . 'end_date')
            || ($fallbackUnprefixed && $prefix !== '' && ($request->filled('start_date') || $request->filled('end_date')));

        $explicitTypeSubmitted = $rawType !== null && $rawType !== '';
        if (!$explicitTypeSubmitted) {
            if ($hasExplicitSingle && !$hasExplicitRange) {
                $rawType = 'dia';
            } elseif ($hasExplicitRange) {
                $rawType = 'rango';
            } else {
                $rawType = $defaultType;
            }
        }

        $allowedTypes = ['dia', 'rango', 'todos'];
        if ($allowPeriodAliases) {
            $allowedTypes[] = 'semana';
            $allowedTypes[] = 'mes';
        }

        $filterType = in_array($rawType, $allowedTypes, true) ? (string) $rawType : $defaultType;
        if (!in_array($filterType, $allowedTypes, true)) {
            $filterType = 'dia';
        }

        $rawSingleInput = $request->input($prefix . 'date');
        if ($rawSingleInput === null && $fallbackUnprefixed && $prefix !== '') {
            $rawSingleInput = $request->input('date');
        }

        $rawStartInput = $request->input($prefix . 'start_date');
        if ($rawStartInput === null && $fallbackUnprefixed && $prefix !== '') {
            $rawStartInput = $request->input('start_date');
        }

        $rawEndInput = $request->input($prefix . 'end_date');
        if ($rawEndInput === null && $fallbackUnprefixed && $prefix !== '') {
            $rawEndInput = $request->input('end_date');
        }

        $selectedDate = self::safeDateString($rawSingleInput, $defaultDate);
        $startDate = self::safeDateString($rawStartInput, $defaultStart);
        $endDate = self::safeDateString($rawEndInput, $defaultEnd);

        // En caso de rango donde solo se envía start o end
        if ($filterType === 'rango' && empty($rawStartInput) && !empty($rawEndInput)) {
            $startDate = $endDate;
        } elseif ($filterType === 'rango' && !empty($rawStartInput) && empty($rawEndInput)) {
            $endDate = $todayStr;
        }

        $wasRestricted = false;

        if (!$canAllDates) {
            if ($filterType === 'todos') {
                $wasRestricted = true;
                $filterType = 'rango';
                $startDate = $minAllowedDate;
                $endDate = $todayStr;
            }

            if ($selectedDate < $minAllowedDate) {
                if (!empty($rawSingleInput)) {
                    $wasRestricted = true;
                }
                $selectedDate = $minAllowedDate;
            }

            if (in_array($filterType, ['semana', 'mes'], true)) {
                $refCarbon = Carbon::parse($selectedDate);
                $computedStart = $filterType === 'semana'
                    ? $refCarbon->copy()->startOfWeek(Carbon::MONDAY)->toDateString()
                    : $refCarbon->copy()->startOfMonth()->toDateString();
                $computedEnd = $filterType === 'semana'
                    ? $refCarbon->copy()->endOfWeek(Carbon::SUNDAY)->toDateString()
                    : $refCarbon->copy()->endOfMonth()->toDateString();

                if ($computedStart < $minAllowedDate) {
                    $wasRestricted = true;
                    $computedStart = $minAllowedDate;
                }
                if ($computedEnd < $computedStart) {
                    $computedEnd = $computedStart;
                }

                $startDate = $computedStart;
                $endDate = $computedEnd;
            } elseif ($filterType === 'rango') {
                if (!empty($rawStartInput) && $startDate < $minAllowedDate) {
                    $wasRestricted = true;
                    $startDate = $minAllowedDate;
                } elseif ($startDate < $minAllowedDate) {
                    $startDate = $minAllowedDate;
                }

                if (!empty($rawEndInput) && $endDate < $minAllowedDate) {
                    $wasRestricted = true;
                    $endDate = $minAllowedDate;
                } elseif ($endDate < $minAllowedDate) {
                    $endDate = $todayStr >= $minAllowedDate ? $todayStr : $minAllowedDate;
                }
            }
        } else {
            if (in_array($filterType, ['semana', 'mes'], true)) {
                $refCarbon = Carbon::parse($selectedDate);
                $startDate = $filterType === 'semana'
                    ? $refCarbon->copy()->startOfWeek(Carbon::MONDAY)->toDateString()
                    : $refCarbon->copy()->startOfMonth()->toDateString();
                $endDate = $filterType === 'semana'
                    ? $refCarbon->copy()->endOfWeek(Carbon::SUNDAY)->toDateString()
                    : $refCarbon->copy()->endOfMonth()->toDateString();
            }
        }

        if ($filterType === 'dia') {
            $startDate = $selectedDate;
            $endDate = $selectedDate;
        } elseif ($filterType === 'todos') {
            $startDate = null;
            $endDate = null;
        } else {
            if ($startDate > $endDate) {
                $tmp = $startDate;
                $startDate = $endDate;
                $endDate = $tmp;
            }
        }

        return [
            'prefix' => $prefix,
            'filter_type' => $filterType,
            'date' => $selectedDate,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'can_all_dates' => $canAllDates,
            'min_allowed_date' => $minAllowedDate,
            'max_past_days' => $maxPastDays,
            'was_restricted' => $wasRestricted,
            'query_start' => $startDate ? Carbon::parse($startDate)->startOfDay() : null,
            'query_end' => $endDate ? Carbon::parse($endDate)->endOfDay() : null,
        ];
    }

    /**
     * Aplica el filtro de fechas resuelto sobre una consulta Eloquent/Builder.
     */
    public static function apply($query, string $column, array $filter, bool $isDateOnlyColumn = false)
    {
        $type = $filter['filter_type'] ?? 'dia';

        if ($type === 'todos') {
            if (!empty($filter['can_all_dates'])) {
                return $query;
            }
            // Resguardo defensivo: nunca permitir histórico abierto sin permiso
            $minDate = $filter['min_allowed_date'] ?? Carbon::today()->subDays(self::MAX_RESTRICTED_DAYS)->toDateString();
            return $isDateOnlyColumn
                ? $query->where($column, '>=', $minDate)
                : $query->where($column, '>=', Carbon::parse($minDate)->startOfDay());
        }

        if ($type === 'dia') {
            $date = $filter['date'] ?? Carbon::today()->toDateString();
            if ($isDateOnlyColumn) {
                return $query->whereDate($column, $date);
            }
            return $query->whereBetween($column, [
                Carbon::parse($date)->startOfDay(),
                Carbon::parse($date)->endOfDay(),
            ]);
        }

        $startDate = $filter['start_date'] ?? null;
        $endDate = $filter['end_date'] ?? null;

        if ($startDate && $endDate) {
            if ($isDateOnlyColumn) {
                return $query->whereBetween($column, [$startDate, $endDate]);
            }
            return $query->whereBetween($column, [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        if ($startDate) {
            $query->where($column, '>=', $isDateOnlyColumn ? $startDate : Carbon::parse($startDate)->startOfDay());
        }
        if ($endDate) {
            $query->where($column, '<=', $isDateOnlyColumn ? $endDate : Carbon::parse($endDate)->endOfDay());
        }

        return $query;
    }

    /**
     * Etiqueta descriptiva en español del filtro aplicado para vistas y PDFs.
     */
    public static function periodLabel(array $filter): string
    {
        $type = $filter['filter_type'] ?? 'dia';
        if ($type === 'todos') {
            return 'Todos los registros (Histórico completo)';
        }
        if ($type === 'dia' && !empty($filter['date'])) {
            return 'Día: ' . Carbon::parse($filter['date'])->format('d/m/Y');
        }
        if (!empty($filter['start_date']) && !empty($filter['end_date'])) {
            $prefix = match ($type) {
                'semana' => 'Semana: ',
                'mes' => 'Mes: ',
                default => 'Entre fechas: ',
            };
            return $prefix
                . Carbon::parse($filter['start_date'])->format('d/m/Y')
                . ' al '
                . Carbon::parse($filter['end_date'])->format('d/m/Y');
        }

        return 'Consulta';
    }

    /**
     * Sufijo seguro para nombres de archivos PDF y Excel.
     */
    public static function fileSuffix(array $filter): string
    {
        $type = $filter['filter_type'] ?? 'dia';
        if ($type === 'todos') {
            return 'todos_los_registros';
        }
        if ($type === 'dia' && !empty($filter['date'])) {
            return $filter['date'];
        }
        if (!empty($filter['start_date']) && !empty($filter['end_date'])) {
            return "{$filter['start_date']}_a_{$filter['end_date']}";
        }

        return Carbon::today()->toDateString();
    }

    protected static function safeDateString(?string $value, string $fallback): string
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return $fallback;
        }

        try {
            return Carbon::parse($trimmed)->toDateString();
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
}

