<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Dto;

readonly class TrackListViewDto
{
    /**
     * @param list<TrackListItemViewDto> $items
     */
    public function __construct(
        public array  $items,
        public string $searchQuery,
        public string $statusFilter,
        public string $sortBy,
        public string $sortDirection,
        public string $createUrl
    ) {
    }
}
