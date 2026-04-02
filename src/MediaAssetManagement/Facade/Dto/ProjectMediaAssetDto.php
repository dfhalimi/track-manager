<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Facade\Dto;

use DateTimeImmutable;

readonly class ProjectMediaAssetDto
{
    public function __construct(
        public string $uuid,
        public string $projectUuid,
        public string $originalFilename,
        public string $storedFilename,
        public string $storagePath,
        public string $mimeType,
        public string $extension,
        public int $sizeBytes,
        public int $widthPixels,
        public int $heightPixels,
        public DateTimeImmutable $uploadedAt
    ) {
    }
}
