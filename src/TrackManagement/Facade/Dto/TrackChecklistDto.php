<?php

declare(strict_types=1);

namespace App\TrackManagement\Facade\Dto;

readonly class TrackChecklistDto
{
    /**
     * @param list<ChecklistItemDto> $items
     */
    public function __construct(
        public string $trackUuid,
        public array  $items,
        public int    $progress,
        public string $status
    ) {
    }
}
