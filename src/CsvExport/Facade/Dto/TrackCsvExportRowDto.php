<?php

declare(strict_types=1);

namespace App\CsvExport\Facade\Dto;

readonly class TrackCsvExportRowDto
{
    public function __construct(
        public string $id,
        public string $title,
        public string $beatName,
        public string $publishingName,
        public string $bpm,
        public string $musicalKey,
        public string $status,
        public string $published,
        public string $cancelled,
        public string $progress,
        public string $projects
    ) {
    }
}
