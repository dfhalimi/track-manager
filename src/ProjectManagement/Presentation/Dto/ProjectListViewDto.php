<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Dto;

readonly class ProjectListViewDto
{
    /**
     * @param list<ProjectListItemViewDto> $items
     * @param list<string> $categoryOptions
     */
    public function __construct(
        public array $items,
        public string $searchQuery,
        public string $categoryFilter,
        public array $categoryOptions,
        public string $sortBy,
        public string $sortDirection,
        public string $createUrl,
        public string $tracksIndexUrl
    ) {
    }
}
