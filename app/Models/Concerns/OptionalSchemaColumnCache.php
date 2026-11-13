<?php

namespace App\Models\Concerns;

final class OptionalSchemaColumnCache
{
    /**
     * @var array<string, bool>
     */
    private static array $cache = [];

    public static function remember(string $key, callable $resolver): bool
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        return self::$cache[$key] = (bool) $resolver();
    }

    public static function flush(): void
    {
        self::$cache = [];
    }
}

