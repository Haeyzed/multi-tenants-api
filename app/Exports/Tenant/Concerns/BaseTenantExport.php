<?php

declare(strict_types=1);

namespace App\Exports\Tenant\Concerns;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @template TModel of object
 *
 * @implements WithMapping<TModel>
 */
abstract class BaseTenantExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, TModel>  $items
     * @param  list<string>|null  $columns
     */
    public function __construct(
        protected readonly Collection $items,
        protected readonly ?array $columns = null,
    ) {}

    /**
     * @return list<string>
     */
    abstract public static function availableColumns(): array;

    /**
     * @return array<string, array{heading: string, map: callable(TModel): (string|null)}>
     */
    abstract protected function columnDefinitions(): array;

    public function collection(): Collection
    {
        return $this->items;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return array_map(
            fn (array $definition) => $definition['heading'],
            $this->resolvedColumns(),
        );
    }

    /**
     * @param  TModel  $row
     * @return list<string|null>
     */
    public function map($row): array
    {
        return array_map(
            fn (array $definition) => ($definition['map'])($row),
            $this->resolvedColumns(),
        );
    }

    /**
     * @return array<string, array{heading: string, map: callable(TModel): (string|null)}>
     */
    protected function resolvedColumns(): array
    {
        $definitions = $this->columnDefinitions();
        $keys = $this->columns ?? static::availableColumns();
        $resolved = [];

        foreach ($keys as $key) {
            if (isset($definitions[$key])) {
                $resolved[$key] = $definitions[$key];
            }
        }

        if ($resolved === []) {
            return $definitions;
        }

        return $resolved;
    }
}
