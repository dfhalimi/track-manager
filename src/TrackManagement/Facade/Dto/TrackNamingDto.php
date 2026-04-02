<?php

declare(strict_types=1);

namespace App\TrackManagement\Facade\Dto;

readonly class TrackNamingDto
{
    /**
     * @param list<float>  $bpms
     * @param list<string> $musicalKeys
     */
    public function __construct(
        public string $trackUuid,
        public int    $trackNumber,
        public string $beatName,
        public array  $bpms,
        public array  $musicalKeys,
        public string $title
    ) {
    }
}
