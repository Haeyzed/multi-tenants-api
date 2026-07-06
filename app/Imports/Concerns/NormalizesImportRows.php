<?php

declare(strict_types=1);

namespace App\Imports\Concerns;

trait NormalizesImportRows
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    protected function nullifyEmpty(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && ! filled($data[$key])) {
                $data[$key] = null;
            }
        }

        return $data;
    }

    protected function parseBoolean(mixed $value, bool $default = true): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
