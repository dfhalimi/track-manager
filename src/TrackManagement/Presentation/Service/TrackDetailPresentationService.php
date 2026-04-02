<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Service;

use App\FileImport\Facade\FileImportFacadeInterface;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use App\TrackManagement\Domain\Enum\TrackStatus;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use App\TrackManagement\Presentation\Dto\ChecklistItemViewDto;
use App\TrackManagement\Presentation\Dto\TrackDetailViewDto;
use App\TrackManagement\Presentation\Dto\TrackFileViewDto;
use App\TrackManagement\Presentation\Dto\TrackProjectMembershipViewDto;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class TrackDetailPresentationService implements TrackDetailPresentationServiceInterface
{
    public function __construct(
        private TrackManagementFacadeInterface $trackManagementFacade,
        private ProjectManagementFacadeInterface $projectManagementFacade,
        private FileImportFacadeInterface      $fileImportFacade,
        private UrlGeneratorInterface          $urlGenerator
    ) {
    }

    public function buildTrackDetailViewDto(string $trackUuid): TrackDetailViewDto
    {
        $track     = $this->trackManagementFacade->getTrackByUuid($trackUuid);
        $checklist = $this->trackManagementFacade->getChecklistByTrackUuid($trackUuid);
        $projectMemberships = $this->projectManagementFacade->getProjectsByTrackUuid($trackUuid);
        $trackFile = $this->fileImportFacade->getCurrentTrackFileByTrackUuid($trackUuid);
        $status    = TrackStatus::from($checklist->status);

        $checklistItems = [];
        foreach ($checklist->items as $item) {
            $checklistItems[] = new ChecklistItemViewDto(
                $item->uuid,
                $item->label,
                $item->isCompleted,
                $item->position,
                $this->urlGenerator->generate('track_management.presentation.checklist.toggle', [
                    'trackUuid' => $trackUuid,
                    'itemUuid'  => $item->uuid,
                ]),
                $this->urlGenerator->generate('track_management.presentation.checklist.rename', [
                    'trackUuid' => $trackUuid,
                    'itemUuid'  => $item->uuid,
                ]),
                $this->urlGenerator->generate('track_management.presentation.checklist.remove', [
                    'trackUuid' => $trackUuid,
                    'itemUuid'  => $item->uuid,
                ])
            );
        }

        $projectMembershipItems = [];
        foreach ($projectMemberships as $membership) {
            $projectMembershipItems[] = new TrackProjectMembershipViewDto(
                $membership->projectTitle,
                $membership->categoryName,
                $membership->position,
                $this->urlGenerator->generate('project_management.presentation.show', ['projectUuid' => $membership->projectUuid])
            );
        }

        return new TrackDetailViewDto(
            $track->uuid,
            $track->trackNumber,
            $track->beatName,
            $track->title,
            $track->publishingName,
            $this->formatBpms($track->bpms),
            $track->musicalKey,
            $track->notes,
            $track->isrc,
            $checklist->progress,
            $status->getLabel(),
            $status->value,
            $checklistItems,
            $projectMembershipItems,
            $trackFile === null ? null : new TrackFileViewDto(
                $trackFile->originalFilename,
                $trackFile->mimeType,
                $trackFile->uploadedAt->format('Y-m-d H:i'),
                $this->urlGenerator->generate('file_import.presentation.play', ['trackUuid' => $trackUuid]),
                $this->urlGenerator->generate('file_import.presentation.upload', ['trackUuid' => $trackUuid]),
                $this->urlGenerator->generate('file_import.presentation.replace', ['trackUuid' => $trackUuid]),
                $this->urlGenerator->generate('file_export.presentation.export', ['trackUuid' => $trackUuid, 'format' => 'mp3']),
                $this->urlGenerator->generate('file_export.presentation.export', ['trackUuid' => $trackUuid, 'format' => 'wav'])
            ),
            $this->urlGenerator->generate('track_management.presentation.index'),
            $this->urlGenerator->generate('track_management.presentation.edit', ['trackUuid' => $trackUuid]),
            $this->urlGenerator->generate('track_management.presentation.delete', ['trackUuid' => $trackUuid]),
            $this->urlGenerator->generate('track_management.presentation.checklist.add', ['trackUuid' => $trackUuid]),
            $this->urlGenerator->generate('track_management.presentation.checklist.reorder', ['trackUuid' => $trackUuid])
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
