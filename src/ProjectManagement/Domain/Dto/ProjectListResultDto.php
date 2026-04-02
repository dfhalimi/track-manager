<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Dto;

readonly class ProjectListResultDto
{
    /**
     * @param list<ProjectListItemDto> $items
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
