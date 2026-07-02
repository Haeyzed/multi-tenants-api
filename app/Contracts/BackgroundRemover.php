<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Removes the background from a local image file.
 */
interface BackgroundRemover
{
    /**
     * Process an image on disk and write the result to the output path.
     *
     * @param  string  $inputPath  Absolute path to the source image.
     * @param  string  $outputPath  Absolute path for the PNG output.
     */
    public function remove(string $inputPath, string $outputPath): void;
}
