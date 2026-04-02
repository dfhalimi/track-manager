<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Dto;

readonly class TrackNamingInputDto
{
    /**
     * @param list<int>    $bpms
     * @param list<string> $musicalKeys
     */
    public function __construct(
        public int    $trackNumber,
        public string $beatName,
        public array  $bpms,
        public array  $musicalKeys
    ) {
    }
}
