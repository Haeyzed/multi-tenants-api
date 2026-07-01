<?php

declare(strict_types=1);

namespace App\Services\Central;

use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Handles spreadsheet export and email attachments via Laravel Excel.
 */
class ExcelExportService
{
    public function download(object $export, string $filename, ?string $writerType = null): BinaryFileResponse
    {
        return Excel::download($export, $filename, $writerType ?? ExcelFormat::XLSX);
    }

    public function raw(object $export, ?string $writerType = null): string
    {
        return Excel::raw($export, $writerType ?? ExcelFormat::XLSX);
    }

    public function writerType(string $type): string
    {
        return match ($type) {
            'csv' => ExcelFormat::CSV,
            default => ExcelFormat::XLSX,
        };
    }

    public function extension(string $type): string
    {
        return match ($type) {
            'csv' => 'csv',
            default => 'xlsx',
        };
    }

    public function mimeType(string $type): string
    {
        return match ($type) {
            'csv' => 'text/csv',
            default => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
    }

    /**
     * @return array{writerType: string, extension: string, mime: string}
     */
    public function resolveFormat(string $type = 'xlsx'): array
    {
        return [
            'writerType' => $this->writerType($type),
            'extension' => $this->extension($type),
            'mime' => $this->mimeType($type),
        ];
    }
}
