<?php

declare(strict_types=1);

namespace App\TrackManagement\Facade\Dto;

readonly class TrackExportDataDto
{
    /**
     * @param list<int> $bpms
     */
    public function __construct(
        public string $trackUuid,
        public int    $trackNumber,
        public string $beatName,
        public string $title,
        public array  $bpms,
        public string $musicalKey
    ) {
    }
}
