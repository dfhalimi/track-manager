<?php

declare(strict_types=1);

namespace App\TrackManagement\Facade\Dto;

readonly class TrackNamingDto
{
    /**
     * @param list<int> $bpms
     */
    public function __construct(
        public string $trackUuid,
        public int    $trackNumber,
        public string $beatName,
        public array  $bpms,
        public string $musicalKey,
        public string $title
    ) {
    }
}
