<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Dto;

readonly class TrackListResultDto
{
    /**
     * @param list<TrackListItemDto> $items
     */
    public function __construct(
        public array $items,
        public int   $totalItems,
        public int   $currentPage,
        public int   $perPage,
        public int   $totalPages
    ) {
    }
}
