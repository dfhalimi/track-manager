<?php

declare(strict_types=1);

namespace App\TrackManagement\Facade;

use App\TrackManagement\Facade\Dto\TrackChecklistDto;
use App\TrackManagement\Facade\Dto\TrackDto;
use App\TrackManagement\Facade\Dto\TrackExportDataDto;
use App\TrackManagement\Facade\Dto\TrackNamingDto;
use App\TrackManagement\Facade\Dto\TrackSelectionDto;

interface TrackManagementFacadeInterface
{
    public function getTrackByUuid(string $trackUuid): TrackDto;

    public function getTrackByTrackNumber(int $trackNumber): ?TrackDto;

    public function getTrackExportData(string $trackUuid): TrackExportDataDto;

    public function getTrackNamingData(string $trackUuid): TrackNamingDto;

    public function trackExists(string $trackUuid): bool;

    public function getChecklistByTrackUuid(string $trackUuid): TrackChecklistDto;

    /**
     * @return list<TrackSelectionDto>
     */
    public function getAllTracksForSelection(): array;
}
