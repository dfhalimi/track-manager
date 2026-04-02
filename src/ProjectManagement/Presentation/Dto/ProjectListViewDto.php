<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Dto;

use App\Common\Presentation\Dto\PaginationLinkViewDto;

readonly class ProjectListViewDto
{
    /**
     * @param list<ProjectListItemViewDto> $items
     * @param list<string>                 $categoryOptions
     * @param list<int>                    $perPageOptions
     * @param list<PaginationLinkViewDto>  $pageLinks
     */
    public function __construct(
        public array   $items,
        public string  $searchQuery,
        public string  $categoryFilter,
        public array   $categoryOptions,
        public string  $sortBy,
        public string  $sortDirection,
        public int     $currentPage,
        public int     $perPage,
        public array   $perPageOptions,
        public int     $totalItems,
        public int     $totalPages,
        public ?string $previousPageUrl,
        public ?string $nextPageUrl,
        public array   $pageLinks,
        public string  $indexUrl,
        public string  $listUrl,
        public string  $suggestionsUrl,
        public string  $createUrl,
        public string  $tracksIndexUrl
    ) {
    }
}
