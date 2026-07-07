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

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    protected function stringifyFields(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            if ($data[$key] === null || $data[$key] === '') {
                $data[$key] = null;

                continue;
            }

            if (! is_string($data[$key])) {
                $data[$key] = $this->stringify($data[$key]);
            }
        }

        return $data;
    }

    protected function stringify(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (floor($value) === $value) {
                return sprintf('%.0f', $value);
            }

            return rtrim(rtrim((string) $value, '0'), '.');
        }

        return trim((string) $value);
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

    protected function parseDecimal(mixed $value, float $default = 0.0): float
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return (float) $value;
    }

    protected function parseInteger(mixed $value, int $default = 0): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return (int) $value;
    }
}
