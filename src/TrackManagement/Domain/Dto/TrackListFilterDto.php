<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Dto;

readonly class TrackListFilterDto
{
    public function __construct(
        public ?string $searchQuery,
        public ?string $statusFilter,
        public string  $sortBy,
        public string  $sortDirection
    ) {
    }
}
