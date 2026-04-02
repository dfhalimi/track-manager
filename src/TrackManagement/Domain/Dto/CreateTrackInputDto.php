<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Dto;

readonly class CreateTrackInputDto
{
    /**
     * @param list<float>  $bpms
     * @param list<string> $musicalKeys
     */
    public function __construct(
        public string  $beatName,
        public string  $title,
        public ?string $publishingName,
        public array   $bpms,
        public array   $musicalKeys,
        public ?string $notes,
        public ?string $isrc
    ) {
    }
}
