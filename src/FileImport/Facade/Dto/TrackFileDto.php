<?php

declare(strict_types=1);

namespace App\FileImport\Facade\Dto;

use DateTimeImmutable;

readonly class TrackFileDto
{
    public function __construct(
        public string            $uuid,
        public string            $trackUuid,
        public string            $originalFilename,
        public string            $storedFilename,
        public string            $storagePath,
        public string            $mimeType,
        public string            $extension,
        public int               $sizeBytes,
        public DateTimeImmutable $uploadedAt
    ) {
    }
}
