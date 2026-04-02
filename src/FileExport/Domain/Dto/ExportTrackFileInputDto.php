<?php

declare(strict_types=1);

namespace App\FileExport\Domain\Dto;

readonly class ExportTrackFileInputDto
{
    public function __construct(
        public string $trackUuid,
        public string $targetFormat
    ) {
    }
}
