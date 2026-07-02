<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Background Removal Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "rembg"
    |
    */

    'driver' => env('BACKGROUND_REMOVAL_DRIVER', 'rembg'),

    /*
    |--------------------------------------------------------------------------
    | Maximum Source File Size
    |--------------------------------------------------------------------------
    |
    | Images larger than this (bytes) cannot be processed for background removal.
    |
    */

    'max_file_size' => (int) env(
        'BACKGROUND_REMOVAL_MAX_FILE_SIZE',
        env('MEDIA_LIBRARY_MAX_FILE_SIZE', 10 * 1024 * 1024),
    ),

    /*
    |--------------------------------------------------------------------------
    | Maximum Image Dimension Before Processing
    |--------------------------------------------------------------------------
    |
    | Large images are downscaled before rembg to reduce CPU time on local dev.
    |
    */

    'max_dimension' => (int) env('BACKGROUND_REMOVAL_MAX_DIMENSION', 1920),

    'rembg' => [
        'binary' => env('REMBG_BINARY', 'rembg'),
        'timeout' => (int) env('REMBG_TIMEOUT', 300),
    ],

];
