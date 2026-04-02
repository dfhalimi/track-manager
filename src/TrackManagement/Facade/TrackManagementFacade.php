<?php

declare(strict_types=1);

namespace App\TrackManagement\Facade;

use App\TrackManagement\Domain\Dto\TrackListFilterDto;
use App\TrackManagement\Domain\Dto\TrackListItemDto;
use App\TrackManagement\Domain\Entity\ChecklistItem;
use App\TrackManagement\Domain\Entity\Track;
use App\TrackManagement\Domain\Service\ChecklistDomainServiceInterface;
use App\TrackManagement\Domain\Service\ProgressCalculatorInterface;
use App\TrackManagement\Domain\Service\TrackManagementDomainServiceInterface;
use App\TrackManagement\Domain\Service\TrackStatusResolverInterface;
use App\TrackManagement\Facade\Dto\ChecklistItemDto;
use App\TrackManagement\Facade\Dto\TrackChecklistDto;
use App\TrackManagement\Facade\Dto\TrackDto;
use App\TrackManagement\Facade\Dto\TrackExportDataDto;
use App\TrackManagement\Facade\Dto\TrackNamingDto;
use App\TrackManagement\Facade\Dto\TrackSelectionDto;
use Throwable;

readonly class TrackManagementFacade implements TrackManagementFacadeInterface
{
    public function __construct(
        private TrackManagementDomainServiceInterface $trackManagementDomainService,
        private ChecklistDomainServiceInterface       $checklistDomainService,
        private ProgressCalculatorInterface           $progressCalculator,
        private TrackStatusResolverInterface          $trackStatusResolver
    ) {
    }

    public function getTrackByUuid(string $trackUuid): TrackDto
    {
        return $this->mapTrackToDto($this->trackManagementDomainService->getTrackByUuid($trackUuid));
    }

    public function getTrackByTrackNumber(int $trackNumber): ?TrackDto
    {
        $track = $this->trackManagementDomainService->getTrackByTrackNumber($trackNumber);

        return $track === null ? null : $this->mapTrackToDto($track);
    }

    public function getTrackExportData(string $trackUuid): TrackExportDataDto
    {
        $track = $this->trackManagementDomainService->getTrackByUuid($trackUuid);

        return new TrackExportDataDto(
            $track->getUuid(),
            $track->getTrackNumber(),
            $track->getBeatName(),
            $track->getTitle(),
            $track->getBpms(),
            $track->getMusicalKeys()
        );
    }

    public function getTrackNamingData(string $trackUuid): TrackNamingDto
    {
        $track = $this->trackManagementDomainService->getTrackByUuid($trackUuid);

        return new TrackNamingDto(
            $track->getUuid(),
            $track->getTrackNumber(),
            $track->getBeatName(),
            $track->getBpms(),
            $track->getMusicalKeys(),
            $track->getTitle()
        );
    }

    public function trackExists(string $trackUuid): bool
    {
        try {
            $this->trackManagementDomainService->getTrackByUuid($trackUuid);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function getChecklistByTrackUuid(string $trackUuid): TrackChecklistDto
    {
        $items = $this->checklistDomainService->getChecklistItemsByTrackUuid($trackUuid);

        return new TrackChecklistDto(
            $trackUuid,
            array_map(
                static fn (ChecklistItem $item): ChecklistItemDto => new ChecklistItemDto(
                    $item->getUuid(),
                    $item->getLabel(),
                    $item->isCompleted(),
                    $item->getPosition()
                ),
                $items
            ),
            $this->progressCalculator->calculateProgress($items),
            $this->trackStatusResolver->resolveStatus($items)->value
        );
    }

    public function getAllTracksForSelection(): array
    {
        $tracks = $this->trackManagementDomainService->getAllTracks(
            new TrackListFilterDto('', '', 'active', 'trackNumber', 'ASC', 1, 10000)
        );

        return array_map(
            static fn (TrackListItemDto $track): TrackSelectionDto => new TrackSelectionDto(
                $track->uuid,
                $track->trackNumber,
                $track->beatName,
                $track->title,
                $track->publishingName
            ),
            $tracks->items
        );
    }

    private function mapTrackToDto(Track $track): TrackDto
    {
        return new TrackDto(
            $track->getUuid(),
            $track->getTrackNumber(),
            $track->getBeatName(),
            $track->getTitle(),
            $track->getPublishingName(),
            $track->getBpms(),
            $track->getMusicalKeys(),
            $track->getNotes(),
            $track->getIsrc(),
            $track->isCancelled(),
            $track->getCreatedAt(),
            $track->getUpdatedAt()
        );
    }
}
