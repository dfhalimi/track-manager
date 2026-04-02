<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Service;

use App\MediaAssetManagement\Facade\MediaAssetManagementFacadeInterface;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use App\ProjectManagement\Presentation\Dto\ProjectDetailViewDto;
use App\ProjectManagement\Presentation\Dto\ProjectMediaAssetViewDto;
use App\ProjectManagement\Presentation\Dto\ProjectTrackAssignmentViewDto;
use App\ProjectManagement\Presentation\Dto\ProjectTrackOptionViewDto;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class ProjectDetailPresentationService implements ProjectDetailPresentationServiceInterface
{
    public function __construct(
        private ProjectManagementFacadeInterface    $projectManagementFacade,
        private TrackManagementFacadeInterface      $trackManagementFacade,
        private MediaAssetManagementFacadeInterface $mediaAssetManagementFacade,
        private UrlGeneratorInterface               $urlGenerator
    ) {
    }

    public function buildProjectDetailViewDto(string $projectUuid): ProjectDetailViewDto
    {
        $project     = $this->projectManagementFacade->getProjectByUuid($projectUuid);
        $assignments = $this->projectManagementFacade->getTrackAssignmentsByProjectUuid($projectUuid);
        $allTracks   = $this->trackManagementFacade->getAllTracksForSelection();
        $mediaAsset  = $this->mediaAssetManagementFacade->getCurrentProjectMediaAssetByProjectUuid($projectUuid);

        $tracksByUuid = [];
        foreach ($allTracks as $track) {
            $tracksByUuid[$track->uuid] = $track;
        }

        $assignedTrackUuids = [];
        $trackItems         = [];
        foreach ($assignments as $assignment) {
            $track = $tracksByUuid[$assignment->trackUuid] ?? null;
            if ($track === null) {
                continue;
            }

            $assignedTrackUuids[] = $assignment->trackUuid;
            $trackItems[]         = new ProjectTrackAssignmentViewDto(
                $assignment->trackUuid,
                $this->buildTrackLabel($track->trackNumber, $track->beatName, $track->publishingName, $track->title),
                $assignment->position,
                $this->urlGenerator->generate('track_management.presentation.show', ['trackUuid' => $assignment->trackUuid]),
                $this->urlGenerator->generate('project_management.presentation.tracks.remove', [
                    'projectUuid' => $projectUuid,
                    'trackUuid'   => $assignment->trackUuid,
                ])
            );
        }

        $availableTracks = [];
        foreach ($allTracks as $track) {
            if (in_array($track->uuid, $assignedTrackUuids, true)) {
                continue;
            }

            $availableTracks[] = new ProjectTrackOptionViewDto(
                $track->uuid,
                $this->buildTrackLabel($track->trackNumber, $track->beatName, $track->publishingName, $track->title)
            );
        }

        return new ProjectDetailViewDto(
            $project->uuid,
            $project->title,
            $project->categoryName,
            $project->createdAt->format('d.m.Y H:i'),
            $trackItems,
            $availableTracks,
            $mediaAsset === null ? null : new ProjectMediaAssetViewDto(
                $mediaAsset->originalFilename,
                $mediaAsset->mimeType,
                $mediaAsset->uploadedAt->format('Y-m-d H:i'),
                sprintf('%d x %d px', $mediaAsset->widthPixels, $mediaAsset->heightPixels),
                $this->urlGenerator->generate('media_asset_management.presentation.preview', ['projectUuid' => $projectUuid]),
                $this->urlGenerator->generate('media_asset_management.presentation.replace', ['projectUuid' => $projectUuid]),
                $this->urlGenerator->generate('media_asset_management.presentation.export', ['projectUuid' => $projectUuid, 'format' => 'jpg']),
                $this->urlGenerator->generate('media_asset_management.presentation.export', ['projectUuid' => $projectUuid, 'format' => 'png'])
            ),
            $this->urlGenerator->generate('project_management.presentation.index'),
            $this->urlGenerator->generate('track_management.presentation.index'),
            $this->urlGenerator->generate('project_management.presentation.edit', ['projectUuid' => $projectUuid]),
            $this->urlGenerator->generate('project_management.presentation.delete', ['projectUuid' => $projectUuid]),
            $this->urlGenerator->generate('project_management.presentation.tracks.add', ['projectUuid' => $projectUuid]),
            $this->urlGenerator->generate('project_management.presentation.tracks.reorder', ['projectUuid' => $projectUuid])
        );
    }

    private function buildTrackLabel(int $trackNumber, string $beatName, ?string $publishingName, string $title): string
    {
        $headline = $publishingName ?? $title;

        return sprintf('#%d - %s (%s)', $trackNumber, $headline, $beatName);
    }
}
