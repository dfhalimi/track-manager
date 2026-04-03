<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Service;

use App\Common\Presentation\Dto\PaginationLinkViewDto;
use App\FileImport\Facade\FileImportFacadeInterface;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use App\TrackManagement\Domain\Dto\TrackListFilterDto;
use App\TrackManagement\Domain\Enum\TrackStatus;
use App\TrackManagement\Domain\Service\TrackManagementDomainServiceInterface;
use App\TrackManagement\Presentation\Dto\TrackFileViewDto;
use App\TrackManagement\Presentation\Dto\TrackListItemViewDto;
use App\TrackManagement\Presentation\Dto\TrackListViewDto;
use App\TrackManagement\Presentation\Dto\TrackProjectBadgeViewDto;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class TrackOverviewPresentationService implements TrackOverviewPresentationServiceInterface
{
    /**
     * @var list<int>
     */
    private const array PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function __construct(
        private TrackManagementDomainServiceInterface $trackManagementDomainService,
        private ProjectManagementFacadeInterface      $projectManagementFacade,
        private FileImportFacadeInterface             $fileImportFacade,
        private UrlGeneratorInterface                 $urlGenerator
    ) {
    }

    public function buildTrackListViewDto(
        ?string $searchQuery,
        ?string $statusFilter,
        ?string $cancelledFilter,
        ?string $sortBy,
        ?string $sortDirection,
        int     $page,
        int     $perPage
    ): TrackListViewDto {
        $filter = $this->buildFilterDto($searchQuery, $statusFilter, $cancelledFilter, $sortBy, $sortDirection, $page, $perPage);
        $result = $this->trackManagementDomainService->getAllTracks($filter);

        $items = [];
        foreach ($result->items as $item) {
            $status        = TrackStatus::from($item->status);
            $trackFile     = $this->fileImportFacade->getCurrentTrackFileByTrackUuid($item->uuid);
            $projectBadges = $this->buildProjectBadges($item->uuid);
            $items[]       = new TrackListItemViewDto(
                $item->uuid,
                $item->trackNumber,
                $item->beatName,
                $item->title,
                $item->publishingName,
                $projectBadges,
                $this->formatBpms($item->bpms),
                $this->formatMusicalKeys($item->musicalKeys),
                $status->getLabel(),
                $status->value,
                $item->cancelled,
                $item->published,
                $item->progress,
                $trackFile !== null,
                $this->urlGenerator->generate('file_import.presentation.upload', ['trackUuid' => $item->uuid]),
                $trackFile === null ? null : new TrackFileViewDto(
                    $trackFile->originalFilename,
                    $trackFile->mimeType,
                    $trackFile->uploadedAt->format('Y-m-d H:i'),
                    $this->urlGenerator->generate('file_import.presentation.play', ['trackUuid' => $item->uuid]),
                    $this->urlGenerator->generate('file_import.presentation.upload', ['trackUuid' => $item->uuid]),
                    $this->urlGenerator->generate('file_import.presentation.replace', ['trackUuid' => $item->uuid]),
                    $this->urlGenerator->generate('file_export.presentation.export', ['trackUuid' => $item->uuid, 'format' => 'mp3']),
                    $this->urlGenerator->generate('file_export.presentation.export', ['trackUuid' => $item->uuid, 'format' => 'wav'])
                ),
                $this->urlGenerator->generate('track_management.presentation.show', ['trackUuid' => $item->uuid]),
                $this->urlGenerator->generate('track_management.presentation.edit', ['trackUuid' => $item->uuid]),
                $this->urlGenerator->generate('track_management.presentation.cancel', ['trackUuid' => $item->uuid])
            );
        }

        return new TrackListViewDto(
            $items,
            (string) ($filter->searchQuery ?? ''),
            (string) ($filter->statusFilter ?? ''),
            (string) ($filter->cancelledFilter ?? ''),
            $filter->sortBy,
            $filter->sortDirection,
            $result->currentPage,
            $result->perPage,
            self::PER_PAGE_OPTIONS,
            $result->totalItems,
            $result->totalPages,
            $result->currentPage > 1 ? $this->buildIndexUrl($filter, $result->currentPage - 1) : null,
            $result->currentPage < $result->totalPages ? $this->buildIndexUrl($filter, $result->currentPage + 1) : null,
            $this->buildPageLinks($filter, $result->currentPage, $result->totalPages),
            $this->urlGenerator->generate('track_management.presentation.index'),
            $this->buildIndexUrl($filter, $result->currentPage),
            $this->urlGenerator->generate('track_management.presentation.list'),
            $this->urlGenerator->generate('track_management.presentation.suggestions'),
            $this->urlGenerator->generate('track_management.presentation.create')
        );
    }

    public function buildTrackSearchSuggestions(
        ?string $searchQuery,
        ?string $statusFilter,
        ?string $cancelledFilter,
        ?string $sortBy,
        ?string $sortDirection,
        int     $limit
    ): array {
        $filter = $this->buildFilterDto($searchQuery, $statusFilter, $cancelledFilter, $sortBy, $sortDirection, 1, max(self::PER_PAGE_OPTIONS));

        return $this->trackManagementDomainService->getTrackSearchSuggestions($filter, $limit);
    }

    /**
     * @param list<float> $bpms
     */
    private function formatBpms(array $bpms): string
    {
        return implode(', ', array_map(fn (float $bpm): string => $this->formatBpm($bpm), $bpms));
    }

    /**
     * @param list<string> $musicalKeys
     */
    private function formatMusicalKeys(array $musicalKeys): string
    {
        return implode(', ', $musicalKeys);
    }

    private function buildFilterDto(
        ?string $searchQuery,
        ?string $statusFilter,
        ?string $cancelledFilter,
        ?string $sortBy,
        ?string $sortDirection,
        int     $page,
        int     $perPage
    ): TrackListFilterDto {
        return new TrackListFilterDto(
            $searchQuery,
            $statusFilter,
            $cancelledFilter,
            (string) ($sortBy ?? 'updatedAt'),
            (string) ($sortDirection ?? 'DESC'),
            max(1, $page),
            $this->normalizePerPage($perPage)
        );
    }

    /**
     * @return list<PaginationLinkViewDto>
     */
    private function buildPageLinks(TrackListFilterDto $filter, int $currentPage, int $totalPages): array
    {
        $pageLinks = [];
        $startPage = max(1, $currentPage - 2);
        $endPage   = min($totalPages, $currentPage + 2);

        for ($page = $startPage; $page <= $endPage; ++$page) {
            $pageLinks[] = new PaginationLinkViewDto(
                $page,
                $this->buildIndexUrl($filter, $page),
                $page === $currentPage
            );
        }

        return $pageLinks;
    }

    private function buildIndexUrl(TrackListFilterDto $filter, int $page): string
    {
        return $this->urlGenerator->generate('track_management.presentation.index', [
            'q'             => $filter->searchQuery,
            'status'        => $filter->statusFilter,
            'cancelled'     => $filter->cancelledFilter,
            'sortBy'        => $filter->sortBy,
            'sortDirection' => $filter->sortDirection,
            'page'          => $page,
            'perPage'       => $filter->perPage,
        ]);
    }

    private function normalizePerPage(int $perPage): int
    {
        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 25;
    }

    private function formatBpm(float $bpm): string
    {
        $formattedBpm = number_format($bpm, 3, '.', '');
        $formattedBpm = rtrim($formattedBpm, '0');

        return rtrim($formattedBpm, '.');
    }

    /**
     * @return list<TrackProjectBadgeViewDto>
     */
    private function buildProjectBadges(string $trackUuid): array
    {
        $publishedBadges   = [];
        $unpublishedBadges = [];

        foreach ($this->projectManagementFacade->getProjectsByTrackUuid($trackUuid) as $membership) {
            $badge = new TrackProjectBadgeViewDto(
                $membership->projectTitle,
                $membership->published
            );

            if ($membership->published) {
                $publishedBadges[] = $badge;

                continue;
            }

            $unpublishedBadges[] = $badge;
        }

        return [...$publishedBadges, ...$unpublishedBadges];
    }
}
