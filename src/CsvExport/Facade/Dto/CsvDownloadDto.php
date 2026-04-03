<?php

declare(strict_types=1);

namespace App\CsvExport\Facade\Dto;

readonly class CsvDownloadDto
{
    public function __construct(
        public string $filePath,
        public string $mimeType,
        public string $downloadFilename
    ) {
    }
}
