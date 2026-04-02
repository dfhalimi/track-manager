<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Facade\Dto;

readonly class ExportedProjectMediaAssetDto
{
    public function __construct(
        public string $filePath,
        public string $downloadFilename,
        public string $mimeType
    ) {
    }
}
