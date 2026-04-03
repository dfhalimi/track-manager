<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Service;

use App\FileImport\Facade\FileImportFacadeInterface;
use App\ProjectManagement\Facade\Dto\ProjectTrackAssignmentDto;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use App\ProjectManagement\Presentation\Dto\ProjectPublishReadinessViewDto;
use App\ProjectManagement\Presentation\Dto\ProjectPublishTrackIssueViewDto;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;

readonly class ProjectPublishReadinessPresentationService implements ProjectPublishReadinessPresentationServiceInterface
{
    public function __construct(
        private ProjectManagementFacadeInterface $projectManagementFacade,
        private TrackManagementFacadeInterface   $trackManagementFacade,
        private FileImportFacadeInterface        $fileImportFacade
    ) {
    }

    public function buildProjectPublishReadinessViewDto(string $projectUuid): ProjectPublishReadinessViewDto
    {
        $assignments = $this->projectManagementFacade->getTrackAssignmentsByProjectUuid($projectUuid);
        if ($assignments === []) {
            return new ProjectPublishReadinessViewDto(true, false, []);
        }

        $trackIssues = array_values(array_filter(array_map(
            fn (ProjectTrackAssignmentDto $assignment): ?ProjectPublishTrackIssueViewDto => $this->buildTrackIssue($assignment),
            $assignments
        )));

        return new ProjectPublishReadinessViewDto($trackIssues !== [], true, $trackIssues);
    }

    private function buildTrackIssue(ProjectTrackAssignmentDto $assignment): ?ProjectPublishTrackIssueViewDto
    {
        $track = $this->trackManagementFacade->getTrackByUuid($assignment->trackUuid);

        $missingRequirements = [];
        if (trim((string) $track->publishingName) === '') {
            $missingRequirements[] = 'Publishing Name';
        }

        if ($this->fileImportFacade->getCurrentTrackFileByTrackUuid($assignment->trackUuid) === null) {
            $missingRequirements[] = 'Datei';
        }

        if ($missingRequirements === []) {
            return null;
        }

        return new ProjectPublishTrackIssueViewDto(
            $assignment->trackUuid,
            $this->buildTrackLabel($track->trackNumber, $track->beatName, $track->publishingName, $track->title),
            $missingRequirements
        );
    }

    private function buildTrackLabel(int $trackNumber, string $beatName, ?string $publishingName, string $title): string
    {
        $headline = $publishingName ?? $title;

        return sprintf('#%d - %s (%s)', $trackNumber, $headline, $beatName);
    }
}
