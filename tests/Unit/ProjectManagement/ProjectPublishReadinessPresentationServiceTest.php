<?php

declare(strict_types=1);

use App\FileImport\Facade\Dto\TrackFileDto;
use App\FileImport\Facade\FileImportFacadeInterface;
use App\ProjectManagement\Facade\Dto\ProjectDto;
use App\ProjectManagement\Facade\Dto\ProjectListFilterInputDto;
use App\ProjectManagement\Facade\Dto\ProjectTrackAssignmentDto;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use App\ProjectManagement\Presentation\Service\ProjectPublishReadinessPresentationService;
use App\TrackManagement\Facade\Dto\TrackChecklistDto;
use App\TrackManagement\Facade\Dto\TrackDto;
use App\TrackManagement\Facade\Dto\TrackExportDataDto;
use App\TrackManagement\Facade\Dto\TrackListFilterInputDto;
use App\TrackManagement\Facade\Dto\TrackNamingDto;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;

describe('ProjectPublishReadinessPresentationService', function (): void {
    it('requires confirmation when a project has no tracks', function (): void {
        $projectManagementFacade = new ProjectPublishReadinessProjectManagementFacadeStub([]);
        $trackManagementFacade   = new ProjectPublishReadinessTrackManagementFacadeStub([]);
        $fileImportFacade        = new ProjectPublishReadinessFileImportFacadeStub([]);

        $service = new ProjectPublishReadinessPresentationService(
            $projectManagementFacade,
            $trackManagementFacade,
            $fileImportFacade
        );

        $readiness = $service->buildProjectPublishReadinessViewDto('project-1');

        expect($readiness->requiresConfirmation)->toBeTrue();
        expect($readiness->hasTracks)->toBeFalse();
        expect($readiness->trackIssues)->toBe([]);
    });

    it('lists missing publishing names and files per assigned track', function (): void {
        $projectManagementFacade = new ProjectPublishReadinessProjectManagementFacadeStub([
            new ProjectPublishReadinessAssignmentLookupDto('project-1', [
                new ProjectTrackAssignmentDto('track-1', 1),
                new ProjectTrackAssignmentDto('track-2', 2),
                new ProjectTrackAssignmentDto('track-3', 3),
            ]),
        ]);
        $trackManagementFacade = new ProjectPublishReadinessTrackManagementFacadeStub([
            createProjectPublishReadinessTrackDto('track-1', 1, 'Beat One', 'Track One', null),
            createProjectPublishReadinessTrackDto('track-2', 2, 'Beat Two', 'Track Two', 'Publishing Two'),
            createProjectPublishReadinessTrackDto('track-3', 3, 'Beat Three', 'Track Three', 'Publishing Three'),
        ]);
        $fileImportFacade = new ProjectPublishReadinessFileImportFacadeStub([
            createProjectPublishReadinessTrackFileDto('track-3'),
        ]);

        $service = new ProjectPublishReadinessPresentationService(
            $projectManagementFacade,
            $trackManagementFacade,
            $fileImportFacade
        );

        $readiness = $service->buildProjectPublishReadinessViewDto('project-1');

        expect($readiness->requiresConfirmation)->toBeTrue();
        expect($readiness->hasTracks)->toBeTrue();
        expect($readiness->trackIssues)->toHaveCount(2);
        expect($readiness->trackIssues[0]->label)->toBe('#1 - Track One (Beat One)');
        expect($readiness->trackIssues[0]->missingRequirements)->toBe(['Publishing Name', 'Datei']);
        expect($readiness->trackIssues[1]->label)->toBe('#2 - Publishing Two (Beat Two)');
        expect($readiness->trackIssues[1]->missingRequirements)->toBe(['Datei']);
    });
});

function createProjectPublishReadinessTrackDto(
    string  $trackUuid,
    int     $trackNumber,
    string  $beatName,
    string  $title,
    ?string $publishingName
): TrackDto {
    $createdAt = createProjectPublishReadinessTestDateTime('2026-04-02 10:00');

    return new TrackDto(
        $trackUuid,
        $trackNumber,
        $beatName,
        $title,
        $publishingName,
        [120.0],
        ['C Minor'],
        null,
        null,
        false,
        $createdAt,
        $createdAt
    );
}

function createProjectPublishReadinessTrackFileDto(string $trackUuid): TrackFileDto
{
    return new TrackFileDto(
        'file-' . $trackUuid,
        $trackUuid,
        'demo.wav',
        'stored-demo.wav',
        '/tmp/stored-demo.wav',
        'audio/wav',
        'wav',
        1024,
        createProjectPublishReadinessTestDateTime('2026-04-02 11:00')
    );
}

function createProjectPublishReadinessTestDateTime(string $dateTime): DateTimeImmutable
{
    [$datePart, $timePart] = explode(' ', $dateTime);
    [$year, $month, $day]  = array_map('intval', explode('-', $datePart));
    [$hour, $minute]       = array_map('intval', explode(':', $timePart));

    return DateAndTimeService::getDateTimeImmutable()
        ->setTimezone(new DateTimeZone('UTC'))
        ->setDate($year, $month, $day)
        ->setTime($hour, $minute);
}

final readonly class ProjectPublishReadinessProjectManagementFacadeStub implements ProjectManagementFacadeInterface
{
    /**
     * @param list<ProjectPublishReadinessAssignmentLookupDto> $assignmentsByProjectUuid
     */
    public function __construct(
        private array $assignmentsByProjectUuid
    ) {
    }

    public function getProjectByUuid(string $projectUuid): ProjectDto
    {
        throw new BadMethodCallException();
    }

    public function projectExists(string $projectUuid): bool
    {
        throw new BadMethodCallException();
    }

    public function getAllProjectCategories(): array
    {
        throw new BadMethodCallException();
    }

    public function getProjectsByFilter(ProjectListFilterInputDto $filter): array
    {
        throw new BadMethodCallException();
    }

    public function getTrackAssignmentsByProjectUuid(string $projectUuid): array
    {
        foreach ($this->assignmentsByProjectUuid as $lookup) {
            if ($lookup->projectUuid === $projectUuid) {
                return $lookup->assignments;
            }
        }

        return [];
    }

    public function getProjectsByTrackUuid(string $trackUuid): array
    {
        throw new BadMethodCallException();
    }

    public function removeTrackFromAllProjects(string $trackUuid): void
    {
        throw new BadMethodCallException();
    }

    public function removeTrackFromActiveProjects(string $trackUuid): void
    {
        throw new BadMethodCallException();
    }
}

final readonly class ProjectPublishReadinessAssignmentLookupDto
{
    /**
     * @param list<ProjectTrackAssignmentDto> $assignments
     */
    public function __construct(
        public string $projectUuid,
        public array  $assignments
    ) {
    }
}

final readonly class ProjectPublishReadinessTrackManagementFacadeStub implements TrackManagementFacadeInterface
{
    /**
     * @var array<string, TrackDto>
     */
    private array $tracksByUuid;

    /**
     * @param list<TrackDto> $tracks
     */
    public function __construct(array $tracks)
    {
        $tracksByUuid = [];
        foreach ($tracks as $track) {
            $tracksByUuid[$track->uuid] = $track;
        }

        $this->tracksByUuid = $tracksByUuid;
    }

    public function getTrackByUuid(string $trackUuid): TrackDto
    {
        return $this->tracksByUuid[$trackUuid] ?? throw new BadMethodCallException();
    }

    public function getTrackByTrackNumber(int $trackNumber): ?TrackDto
    {
        throw new BadMethodCallException();
    }

    public function getTrackExportData(string $trackUuid): TrackExportDataDto
    {
        throw new BadMethodCallException();
    }

    public function getTrackNamingData(string $trackUuid): TrackNamingDto
    {
        throw new BadMethodCallException();
    }

    public function trackExists(string $trackUuid): bool
    {
        throw new BadMethodCallException();
    }

    public function getChecklistByTrackUuid(string $trackUuid): TrackChecklistDto
    {
        throw new BadMethodCallException();
    }

    public function getTracksByFilter(TrackListFilterInputDto $filter): array
    {
        throw new BadMethodCallException();
    }

    public function getAllTracksForSelection(): array
    {
        throw new BadMethodCallException();
    }
}

final readonly class ProjectPublishReadinessFileImportFacadeStub implements FileImportFacadeInterface
{
    /**
     * @var array<string, TrackFileDto>
     */
    private array $trackFilesByTrackUuid;

    /**
     * @param list<TrackFileDto> $trackFiles
     */
    public function __construct(array $trackFiles)
    {
        $trackFilesByTrackUuid = [];
        foreach ($trackFiles as $trackFile) {
            $trackFilesByTrackUuid[$trackFile->trackUuid] = $trackFile;
        }

        $this->trackFilesByTrackUuid = $trackFilesByTrackUuid;
    }

    public function getCurrentTrackFileByTrackUuid(string $trackUuid): ?TrackFileDto
    {
        return $this->trackFilesByTrackUuid[$trackUuid] ?? null;
    }

    public function deleteCurrentTrackFileByTrackUuid(string $trackUuid): void
    {
        throw new BadMethodCallException();
    }
}
