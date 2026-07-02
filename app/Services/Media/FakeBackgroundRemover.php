<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Contracts\BackgroundRemover;

/**
 * Test double that writes a minimal transparent PNG without calling rembg.
 */
class FakeBackgroundRemover implements BackgroundRemover
{
    public function remove(string $inputPath, string $outputPath): void
    {
        $image = imagecreatetruecolor(1, 1);

        if ($image === false) {
            copy($inputPath, $outputPath);

            return;
        }

        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        imagepng($image, $outputPath);
        imagedestroy($image);
    }
}
