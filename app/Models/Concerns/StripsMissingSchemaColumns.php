<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

trait StripsMissingSchemaColumns
{
    /**
     * Registra el listener saving para omitir columnas opcionales aún no migradas.
     */
    protected static function bootStripsMissingSchemaColumns(): void
    {
        static::saving(function (Model $model) {
            static::stripMissingOptionalColumns($model);
        });
    }

    /**
     * Mapa de columnas opcionales del modelo.
     * Valor true indica que debe fallar de forma explícita si trae un valor no vacío y falta la migración.
     *
     * @return array<int|string, string|bool>
     */
    protected static function optionalSchemaColumns(): array
    {
        return [];
    }

    /**
     * Limpia el caché estático de columnas verificadas (útil en pruebas unitarias).
     */
    public static function flushOptionalSchemaColumnCache(): void
    {
        OptionalSchemaColumnCache::flush();
    }

    /**
     * Omite atributos nulos/por defecto cuya columna aún no exista en la tabla del modelo.
     */
    protected static function stripMissingOptionalColumns(Model $model): void
    {
        $table = $model->getTable();
        $connectionName = $model->getConnectionName();
        $attributes = $model->getAttributes();

        foreach (static::optionalSchemaColumns() as $key => $value) {
            $column = is_int($key) ? (string) $value : (string) $key;
            $requireWhenPopulated = is_int($key) ? false : (bool) $value;

            if (!array_key_exists($column, $attributes)) {
                continue;
            }

            if (static::modelTableHasColumn($connectionName, $table, $column)) {
                continue;
            }

            $rawValue = $attributes[$column];
            $isEmptyDefault = ($rawValue === null || $rawValue === '' || $rawValue === false || $rawValue === 0 || $rawValue === '0' || $rawValue === 0.0);

            if ($requireWhenPopulated && !$isEmptyDefault) {
                throw new \RuntimeException(
                    "Faltan migraciones de base de datos por ejecutar (php artisan migrate) para guardar '{$column}' en '{$table}'."
                );
            }

            $model->offsetUnset($column);
        }
    }

    /**
     * Consulta (con caché por petición) si la tabla posee una columna dada.
     */
    protected static function modelTableHasColumn(?string $connectionName, string $table, string $column): bool
    {
        $cacheKey = ($connectionName ?: '__default__') . ':' . $table . ':' . $column;

        return OptionalSchemaColumnCache::remember($cacheKey, function () use ($connectionName, $table, $column) {
            return $connectionName
                ? Schema::connection($connectionName)->hasColumn($table, $column)
                : Schema::hasColumn($table, $column);
        });
    }
}
