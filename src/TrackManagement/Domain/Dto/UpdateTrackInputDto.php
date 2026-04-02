<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Dto;

readonly class UpdateTrackInputDto
{
    /**
     * @param list<int> $bpms
     */
    public function __construct(
        public string  $trackUuid,
        public string  $beatName,
        public string  $title,
        public ?string $publishingName,
        public array   $bpms,
        public string  $musicalKey,
        public ?string $notes,
        public ?string $isrc,
        public bool    $replaceTitleWithSuggestion
    ) {
    }
}
