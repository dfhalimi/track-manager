<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Dto;

use DateTimeImmutable;

readonly class TrackListItemDto
{
    /**
     * @param list<int>    $bpms
     * @param list<string> $musicalKeys
     */
    public function __construct(
        public string            $uuid,
        public int               $trackNumber,
        public string            $beatName,
        public string            $title,
        public ?string           $publishingName,
        public array             $bpms,
        public array             $musicalKeys,
        public int               $progress,
        public string            $status,
        public bool              $hasCurrentFile,
        public DateTimeImmutable $updatedAt
    ) {
    }
}
