<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Service;

use App\TrackManagement\Presentation\Dto\TrackListViewDto;

interface TrackOverviewPresentationServiceInterface
{
    public function buildTrackListViewDto(
        ?string $searchQuery,
        ?string $statusFilter,
        ?string $sortBy,
        ?string $sortDirection,
        int     $page,
        int     $perPage
    ): TrackListViewDto;

    /**
     * @return list<string>
     */
    public function buildTrackSearchSuggestions(
        ?string $searchQuery,
        ?string $statusFilter,
        ?string $sortBy,
        ?string $sortDirection,
        int     $limit
    ): array;
}
