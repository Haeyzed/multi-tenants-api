<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central\Concerns;

use App\Http\Controllers\ApiController;
use App\Models\Central\CentralUser;
use App\Services\Central\ExcelExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @mixin ApiController
 */
trait ExportsSpreadsheets
{
    protected function spreadsheetExport(
        Request $request,
        object  $export,
        string  $basename,
        string  $emailSubject,
        string  $emailBody,
    ): BinaryFileResponse|JsonResponse
    {
        $type = (string)$request->input('type', 'xlsx');
        $format = app(ExcelExportService::class)->resolveFormat($type);
        $filename = "{$basename}.{$format['extension']}";

        if (($request->input('delivery', 'download')) === 'email') {
            $content = app(ExcelExportService::class)->raw($export, $format['writerType']);
            $recipient = $request->filled('recipient_id')
                ? CentralUser::query()->findOrFail($request->integer('recipient_id'))
                : $request->user();

            Mail::raw($emailBody, function ($message) use ($recipient, $content, $filename, $format, $emailSubject): void {
                $message->to($recipient->email)
                    ->subject($emailSubject)
                    ->attachData($content, $filename, ['mime' => $format['mime']]);
            });

            return $this->success(null, 'Export sent successfully.');
        }

        return app(ExcelExportService::class)->download($export, $filename, $format['writerType']);
    }

    protected function importSampleDownload(Request $request, object $sample, string $basename): BinaryFileResponse
    {
        $type = (string)$request->validate([
            'type' => ['sometimes', 'in:xlsx,csv'],
        ])['type'] ?? 'xlsx';

        $format = app(ExcelExportService::class)->resolveFormat($type);
        $filename = "{$basename}-import-sample.{$format['extension']}";

        return app(ExcelExportService::class)->download($sample, $filename, $format['writerType']);
    }
}
