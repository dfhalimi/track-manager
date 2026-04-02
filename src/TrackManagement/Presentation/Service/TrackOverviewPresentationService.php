<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Service;

use App\FileImport\Facade\FileImportFacadeInterface;
use App\TrackManagement\Domain\Dto\TrackListFilterDto;
use App\TrackManagement\Domain\Enum\TrackStatus;
use App\TrackManagement\Domain\Service\TrackManagementDomainServiceInterface;
use App\TrackManagement\Presentation\Dto\TrackListItemViewDto;
use App\TrackManagement\Presentation\Dto\TrackListViewDto;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class TrackOverviewPresentationService implements TrackOverviewPresentationServiceInterface
{
    public function __construct(
        private TrackManagementDomainServiceInterface $trackManagementDomainService,
        private FileImportFacadeInterface             $fileImportFacade,
        private UrlGeneratorInterface                 $urlGenerator
    ) {
    }

    public function buildTrackListViewDto(
        ?string $searchQuery,
        ?string $statusFilter,
        ?string $sortBy,
        ?string $sortDirection
    ): TrackListViewDto {
        $result = $this->trackManagementDomainService->getAllTracks(
            new TrackListFilterDto(
                $searchQuery,
                $statusFilter,
                $sortBy        ?? 'updatedAt',
                $sortDirection ?? 'DESC'
            )
        );

        $items = [];
        foreach ($result->items as $item) {
            $status  = TrackStatus::from($item->status);
            $items[] = new TrackListItemViewDto(
                $item->uuid,
                $item->trackNumber,
                $item->beatName,
                $item->title,
                $item->publishingName,
                $this->formatBpms($item->bpms),
                $item->musicalKey,
                $status->getLabel(),
                $status->value,
                $item->progress,
                $this->fileImportFacade->getCurrentTrackFileByTrackUuid($item->uuid) !== null,
                $this->urlGenerator->generate('track_management.presentation.show', ['trackUuid' => $item->uuid]),
                $this->urlGenerator->generate('track_management.presentation.edit', ['trackUuid' => $item->uuid]),
                $this->urlGenerator->generate('track_management.presentation.delete', ['trackUuid' => $item->uuid])
            );
        }

        return new TrackListViewDto(
            $items,
            (string) ($searchQuery ?? ''),
            (string) ($statusFilter ?? ''),
            (string) ($sortBy ?? 'updatedAt'),
            (string) ($sortDirection ?? 'DESC'),
            $this->urlGenerator->generate('track_management.presentation.create')
        );
    }

    /**
     * @param list<int> $bpms
     */
    private function formatBpms(array $bpms): string
    {
        return implode(', ', array_map(static fn (int $bpm): string => (string) $bpm, $bpms));
    }
}
