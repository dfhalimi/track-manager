<?php

declare(strict_types=1);

namespace App\CsvExport\Facade\Dto;

readonly class ProjectCsvExportRowDto
{
    public function __construct(
        public string $id,
        public string $title,
        public string $category,
        public string $artists,
        public string $published,
        public string $cancelled,
        public string $trackCount,
        public string $tracks
    ) {
    }
}
