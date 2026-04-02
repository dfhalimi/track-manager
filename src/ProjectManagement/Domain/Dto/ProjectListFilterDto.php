<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Dto;

readonly class ProjectListFilterDto
{
    public function __construct(
        public ?string $searchQuery,
        public ?string $categoryFilter,
        public ?string $cancelledFilter,
        public ?string $sortBy,
        public ?string $sortDirection,
        public int     $page,
        public int     $perPage
    ) {
    }
}
