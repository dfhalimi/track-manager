<?php

declare(strict_types=1);

namespace App\TrackManagement\Facade\Dto;

readonly class TrackSelectionDto
{
    public function __construct(
        public string $uuid,
        public int $trackNumber,
        public string $beatName,
        public string $title,
        public ?string $publishingName
    ) {
    }
}
