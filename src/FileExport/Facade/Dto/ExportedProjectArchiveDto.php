<?php

declare(strict_types=1);

namespace App\FileExport\Facade\Dto;

readonly class ExportedProjectArchiveDto
{
    public function __construct(
        public string $filePath,
        public string $downloadFilename,
        public string $mimeType
    ) {
    }
}
