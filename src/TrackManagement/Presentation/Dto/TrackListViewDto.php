<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Dto;

use App\Common\Presentation\Dto\PaginationLinkViewDto;

readonly class TrackListViewDto
{
    /**
     * @param list<TrackListItemViewDto>  $items
     * @param list<int>                   $perPageOptions
     * @param list<PaginationLinkViewDto> $pageLinks
     */
    public function __construct(
        public array   $items,
        public string  $searchQuery,
        public string  $statusFilter,
        public string  $cancelledFilter,
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
        public string  $currentUrl,
        public string  $listUrl,
        public string  $suggestionsUrl,
        public string  $createUrl
    ) {
    }
}
