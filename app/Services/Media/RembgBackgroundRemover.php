<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Contracts\BackgroundRemover;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException as SymfonyProcessTimedOutException;

/**
 * Removes image backgrounds using the local rembg CLI.
 */
class RembgBackgroundRemover implements BackgroundRemover
{
    public function remove(string $inputPath, string $outputPath): void
    {
        $binary = (string) config('background-removal.rembg.binary', 'rembg');
        $timeout = (int) config('background-removal.rembg.timeout', 300);

        try {
            $result = Process::timeout($timeout)->run([
                $binary,
                'i',
                $inputPath,
                $outputPath,
            ]);
        } catch (ProcessTimedOutException|SymfonyProcessTimedOutException) {
            // Windows may keep input file handles open briefly after the process is killed.
            usleep(500_000);

            throw new RuntimeException(
                "Background removal timed out after {$timeout} seconds. "
                .'Large images take longer on CPU — try a smaller image or increase REMBG_TIMEOUT in .env.',
            );
        }

        if (! $result->successful()) {
            $error = trim($result->errorOutput()) ?: trim($result->output());

            throw new RuntimeException(
                $error !== ''
                    ? "Background removal failed: {$error}"
                    : 'Background removal failed. Ensure rembg is installed and REMBG_BINARY is configured.',
            );
        }

        if (! is_file($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('Background removal produced an empty file.');
        }
    }
}
