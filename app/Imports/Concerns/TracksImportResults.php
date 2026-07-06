<?php

declare(strict_types=1);

namespace App\Imports\Concerns;

trait TracksImportResults
{
    private int $imported = 0;

    public function importedCount(): int
    {
        return $this->imported;
    }

    protected function incrementImported(): void
    {
        $this->imported++;
    }
}
