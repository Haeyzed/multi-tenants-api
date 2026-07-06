<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Concerns;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @mixin ApiController
 */
trait ImportsSpreadsheets
{
    protected function runSpreadsheetImport(
        object $import,
        UploadedFile $file,
        string $successMessage,
    ): JsonResponse {
        Excel::import($import, $file);

        $failures = method_exists($import, 'failures')
            ? collect($import->failures())
            : collect();

        $imported = method_exists($import, 'importedCount')
            ? $import->importedCount()
            : null;

        if (($imported ?? 0) === 0 && $failures->isNotEmpty()) {
            return $this->validationError(
                $this->formatImportFailures($failures),
                'Import failed. No records were imported.',
            );
        }

        $message = $successMessage;

        if ($failures->isNotEmpty()) {
            $message = sprintf(
                '%d record(s) imported, %d row(s) skipped.',
                $imported ?? 0,
                $failures->count(),
            );
        } elseif ($imported !== null) {
            $message = sprintf('%d record(s) imported successfully.', $imported);
        }

        return $this->success([
            'imported' => $imported,
            'failed' => $failures->count(),
            'failures' => $failures->isNotEmpty()
                ? $this->formatImportFailures($failures)
                : [],
        ], $message);
    }

    /**
     * @return list<array{row: int, attribute: string, errors: list<string>}>
     */
    protected function formatImportFailures(Collection $failures): array
    {
        return $failures
            ->map(function ($failure): array {
                return [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                ];
            })
            ->values()
            ->all();
    }
}
