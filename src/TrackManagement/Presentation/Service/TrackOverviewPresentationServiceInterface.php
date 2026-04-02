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
        ?string $sortDirection
    ): TrackListViewDto;
}
