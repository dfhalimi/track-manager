<?php

declare(strict_types=1);

namespace App\FileImport\Infrastructure\Dto;

readonly class StoredFileDto
{
    public function __construct(
        public string $storedFilename,
        public string $storagePath,
        public string $extension,
        public string $mimeType,
        public int    $sizeBytes
    ) {
    }
}
