<?php

declare(strict_types=1);

namespace App\TrackManagement\Facade\Dto;

use DateTimeImmutable;

readonly class TrackDto
{
    /**
     * @param list<int> $bpms
     */
    public function __construct(
        public string            $uuid,
        public int               $trackNumber,
        public string            $beatName,
        public string            $title,
        public ?string           $publishingName,
        public array             $bpms,
        public string            $musicalKey,
        public ?string           $notes,
        public ?string           $isrc,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt
    ) {
    }
}
