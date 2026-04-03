<?php

declare(strict_types=1);

namespace App\TrackManagement\Facade\Dto;

readonly class TrackListFilterInputDto
{
    public function __construct(
        public ?string $searchQuery,
        public ?string $statusFilter,
        public ?string $cancelledFilter,
        public string  $sortBy,
        public string  $sortDirection
    ) {
    }
}
