<?php

declare(strict_types=1);

namespace App\TrackManagement\Domain\Service;

use App\TrackManagement\Domain\Dto\CreateTrackInputDto;
use App\TrackManagement\Domain\Dto\TrackListFilterDto;
use App\TrackManagement\Domain\Dto\TrackListResultDto;
use App\TrackManagement\Domain\Dto\UpdateTrackInputDto;
use App\TrackManagement\Domain\Entity\Track;

interface TrackManagementDomainServiceInterface
{
    public function getNextTrackNumberPreview(): int;

    public function createNewTrack(CreateTrackInputDto $input): Track;

    public function updateTrack(UpdateTrackInputDto $input): Track;

    public function deleteTrack(string $trackUuid): void;

    public function cancelTrack(string $trackUuid): Track;

    public function reactivateTrack(string $trackUuid): Track;

    public function getTrackByUuid(string $trackUuid): Track;

    public function getTrackByTrackNumber(int $trackNumber): ?Track;

    public function getAllTracks(TrackListFilterDto $filter): TrackListResultDto;

    /**
     * @return list<string>
     */
    public function getTrackSearchSuggestions(TrackListFilterDto $filter, int $limit): array;
}
