<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Service;

use App\Common\Service\LocalizedDateTimeService;
use App\FileImport\Facade\FileImportFacadeInterface;
use App\MediaAssetManagement\Facade\MediaAssetManagementFacadeInterface;
use App\ProjectManagement\Facade\Dto\ProjectTrackAssignmentDto;
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
        private FileImportFacadeInterface           $fileImportFacade,
        private MediaAssetManagementFacadeInterface $mediaAssetManagementFacade,
        private LocalizedDateTimeService            $localizedDateTimeService,
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

        $assignedTrackUuids  = [];
        $trackItems          = [];
        $hasExportableTracks = false;
        foreach ($assignments as $assignment) {
            $track = $tracksByUuid[$assignment->trackUuid] ?? null;
            if ($track === null) {
                continue;
            }

            $hasAudioFile         = $this->fileImportFacade->getCurrentTrackFileByTrackUuid($assignment->trackUuid) !== null;
            $assignedTrackUuids[] = $assignment->trackUuid;
            $hasExportableTracks  = $hasExportableTracks || $hasAudioFile;
            $trackItems[]         = new ProjectTrackAssignmentViewDto(
                $assignment->trackUuid,
                $this->buildTrackLabel($track->trackNumber, $track->beatName, $track->publishingName, $track->title),
                $assignment->position,
                $this->urlGenerator->generate('track_management.presentation.show', ['trackUuid' => $assignment->trackUuid]),
                $this->urlGenerator->generate('project_management.presentation.tracks.remove', [
                    'projectUuid' => $projectUuid,
                    'trackUuid'   => $assignment->trackUuid,
                ]),
                $hasAudioFile,
                $hasAudioFile ? $this->urlGenerator->generate('file_export.presentation.export', ['trackUuid' => $assignment->trackUuid, 'format' => 'mp3']) : null,
                $hasAudioFile ? $this->urlGenerator->generate('file_export.presentation.export', ['trackUuid' => $assignment->trackUuid, 'format' => 'wav']) : null
            );
        }

        $availableTracks = [];
        foreach ($allTracks as $track) {
            if (in_array($track->uuid, $assignedTrackUuids, true)) {
                continue;
            }

            $availableTracks[] = new ProjectTrackOptionViewDto(
                $track->uuid,
                $this->buildTrackOptionLabel($track->trackNumber, $track->beatName, $track->publishingName, $track->title)
            );
        }

        return new ProjectDetailViewDto(
            $project->uuid,
            $project->title,
            $project->categoryName,
            $this->localizedDateTimeService->formatForDisplay($project->createdAt),
            $project->cancelled,
            $project->published,
            $project->publishedAt === null ? null : $this->localizedDateTimeService->formatForDisplay($project->publishedAt),
            $this->localizedDateTimeService->formatForInput(new \DateTimeImmutable()),
            $hasExportableTracks,
            $this->urlGenerator->generate('file_export.presentation.project_export', ['projectUuid' => $projectUuid, 'format' => 'mp3']),
            $this->urlGenerator->generate('file_export.presentation.project_export', ['projectUuid' => $projectUuid, 'format' => 'wav']),
            $trackItems,
            $availableTracks,
            $this->urlGenerator->generate('project_management.presentation.tracks.suggestions', ['projectUuid' => $projectUuid]),
            $mediaAsset === null ? null : new ProjectMediaAssetViewDto(
                $mediaAsset->originalFilename,
                $mediaAsset->mimeType,
                $this->localizedDateTimeService->formatForDisplay($mediaAsset->uploadedAt),
                sprintf('%d x %d px', $mediaAsset->widthPixels, $mediaAsset->heightPixels),
                $this->urlGenerator->generate('media_asset_management.presentation.preview', ['projectUuid' => $projectUuid]),
                $this->urlGenerator->generate('media_asset_management.presentation.replace', ['projectUuid' => $projectUuid]),
                $this->urlGenerator->generate('media_asset_management.presentation.export', ['projectUuid' => $projectUuid, 'format' => 'jpg']),
                $this->urlGenerator->generate('media_asset_management.presentation.export', ['projectUuid' => $projectUuid, 'format' => 'png'])
            ),
            $this->urlGenerator->generate('activity_history.presentation.project_modal', ['projectUuid' => $projectUuid]),
            $this->urlGenerator->generate('project_management.presentation.index'),
            $this->urlGenerator->generate('track_management.presentation.index'),
            $this->urlGenerator->generate('project_management.presentation.edit', ['projectUuid' => $projectUuid]),
            $this->urlGenerator->generate('project_management.presentation.cancel', ['projectUuid' => $projectUuid]),
            $this->urlGenerator->generate('project_management.presentation.reactivate', ['projectUuid' => $projectUuid]),
            $this->urlGenerator->generate('project_management.presentation.publish', ['projectUuid' => $projectUuid]),
            $this->urlGenerator->generate('project_management.presentation.unpublish', ['projectUuid' => $projectUuid]),
            $this->urlGenerator->generate('project_management.presentation.tracks.add', ['projectUuid' => $projectUuid]),
            $this->urlGenerator->generate('project_management.presentation.tracks.reorder', ['projectUuid' => $projectUuid])
        );
    }

    public function buildAvailableTrackSuggestions(string $projectUuid, ?string $query, int $limit): array
    {
        if ($this->projectManagementFacade->getProjectByUuid($projectUuid)->cancelled) {
            return [];
        }

        $searchQuery = trim((string) $query);
        if ($searchQuery === '') {
            return [];
        }

        $assignedTrackUuids = array_map(
            static fn (ProjectTrackAssignmentDto $assignment): string => $assignment->trackUuid,
            $this->projectManagementFacade->getTrackAssignmentsByProjectUuid($projectUuid)
        );

        $suggestions = [];
        foreach ($this->trackManagementFacade->getAllTracksForSelection() as $track) {
            if (in_array($track->uuid, $assignedTrackUuids, true)) {
                continue;
            }

            if (!$this->matchesSuggestionQuery($track->title, $track->publishingName, $searchQuery)) {
                continue;
            }

            $suggestions[] = new ProjectTrackOptionViewDto(
                $track->uuid,
                $this->buildTrackOptionLabel($track->trackNumber, $track->beatName, $track->publishingName, $track->title)
            );

            if (count($suggestions) >= $limit) {
                break;
            }
        }

        return $suggestions;
    }

    private function buildTrackLabel(int $trackNumber, string $beatName, ?string $publishingName, string $title): string
    {
        $headline = $publishingName ?? $title;

        return sprintf('#%d - %s (%s)', $trackNumber, $headline, $beatName);
    }

    private function buildTrackOptionLabel(int $trackNumber, string $beatName, ?string $publishingName, string $title): string
    {
        $normalizedPublishingName = $publishingName === null ? '' : mb_strtolower(trim($publishingName));
        $normalizedTitle          = mb_strtolower(trim($title));

        if ($normalizedPublishingName !== '' && $normalizedPublishingName !== $normalizedTitle) {
            return sprintf('#%d - %s / %s (%s)', $trackNumber, $publishingName, $title, $beatName);
        }

        return $this->buildTrackLabel($trackNumber, $beatName, $publishingName, $title);
    }

    private function matchesSuggestionQuery(string $title, ?string $publishingName, string $query): bool
    {
        if (mb_stripos($title, $query) !== false) {
            return true;
        }

        if ($publishingName === null) {
            return false;
        }

        return mb_stripos($publishingName, $query) !== false;
    }
}
