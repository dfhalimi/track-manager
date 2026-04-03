<?php

declare(strict_types=1);

namespace App\ProjectManagement\Facade\Dto;

readonly class ProjectListFilterInputDto
{
    public function __construct(
        public ?string $searchQuery,
        public ?string $categoryFilter,
        public ?string $cancelledFilter,
        public string  $sortBy,
        public string  $sortDirection
    ) {
    }
}
