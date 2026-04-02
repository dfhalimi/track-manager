<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Infrastructure\Dto;

readonly class StoredProjectMediaAssetDto
{
    public function __construct(
        public string $storedFilename,
        public string $storagePath,
        public string $extension,
        public string $mimeType,
        public int    $sizeBytes,
        public int    $widthPixels,
        public int    $heightPixels
    ) {
    }
}
