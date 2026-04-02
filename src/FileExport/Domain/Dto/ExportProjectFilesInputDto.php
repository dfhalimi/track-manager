<?php

declare(strict_types=1);

namespace App\FileExport\Domain\Dto;

readonly class ExportProjectFilesInputDto
{
    public function __construct(
        public string $projectUuid,
        public string $targetFormat
    ) {
    }
}
