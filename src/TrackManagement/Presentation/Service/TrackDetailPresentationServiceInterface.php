<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Service;

use App\TrackManagement\Presentation\Dto\TrackDetailViewDto;

interface TrackDetailPresentationServiceInterface
{
    public function buildTrackDetailViewDto(string $trackUuid): TrackDetailViewDto;
}
