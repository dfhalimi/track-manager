<?php

declare(strict_types=1);

use App\CsvExport\Domain\Service\CsvWriterService;
use App\CsvExport\Facade\CsvExportFacade;
use App\ProjectManagement\Facade\Dto\ProjectCategoryDto;
use App\ProjectManagement\Facade\Dto\ProjectDto;
use App\ProjectManagement\Facade\Dto\ProjectListExportItemDto;
use App\ProjectManagement\Facade\Dto\ProjectListFilterInputDto;
use App\ProjectManagement\Facade\Dto\ProjectTrackAssignmentDto;
use App\ProjectManagement\Facade\Dto\TrackProjectMembershipDto;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use App\TrackManagement\Facade\Dto\TrackChecklistDto;
use App\TrackManagement\Facade\Dto\TrackDto;
use App\TrackManagement\Facade\Dto\TrackExportDataDto;
use App\TrackManagement\Facade\Dto\TrackListExportItemDto;
use App\TrackManagement\Facade\Dto\TrackListFilterInputDto;
use App\TrackManagement\Facade\Dto\TrackNamingDto;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;

describe('CsvExportFacade', function (): void {
    it('exports tracks as csv with active projects in one column', function (): void {
        $facade = new CsvExportFacade(
            new CsvExportTrackManagementFacadeStub(
                [new TrackListExportItemDto('track-1', 7, 'Beat A', 'Alpha Title', 'Release Alpha', [120.0, 120.5], ['C Maj', 'D Min'], 66, 'in_progress', false, true)],
                []
            ),
            new CsvExportProjectManagementFacadeStub(
                [],
                [new CsvExportTrackMembershipLookupDto('track-1', [
                    new CsvExportTrackMembershipLookupItemDto('project-1', 'Project One', 'Single', 1, true, false),
                    new CsvExportTrackMembershipLookupItemDto('project-2', 'Archived Project', 'EP', 2, false, true),
                ])],
                []
            ),
            new CsvWriterService()
        );

        $download = $facade->exportTracks(new TrackListFilterInputDto('alpha', 'in_progress', 'active', 'updatedAt', 'DESC'));
        $rows     = readCsvRows($download->filePath);

        expect($download->downloadFilename)->toBe('tracks-export.csv');
        expect($rows[0])->toBe(['ID', 'Titel', 'Beat Name', 'Publishing Name', 'BPM', 'Key', 'Status', 'Published', 'Archiviert', 'Progress', 'Projekte']);
        expect($rows[1])->toBe(['7', 'Alpha Title', 'Beat A', 'Release Alpha', '120, 120.5', 'C Maj, D Min', 'In Progress', 'ja', 'nein', '66%', 'Project One']);

        unlink($download->filePath);
    });

    it('exports projects as csv with artist list and publishing-name fallback', function (): void {
        $facade = new CsvExportFacade(
            new CsvExportTrackManagementFacadeStub(
                [],
                [
                    new TrackDto('track-1', 1, 'Beat A', 'Alpha Title', null, [120.0], ['C Maj'], null, null, false, DateAndTimeService::getDateTimeImmutable(), DateAndTimeService::getDateTimeImmutable()),
                    new TrackDto('track-2', 2, 'Beat B', 'Beta Title', 'Release Beta', [121.0], ['D Min'], null, null, false, DateAndTimeService::getDateTimeImmutable(), DateAndTimeService::getDateTimeImmutable()),
                ]
            ),
            new CsvExportProjectManagementFacadeStub(
                [new ProjectListExportItemDto('project-1', 'Spring Tape', 'EP', ['Artist One', 'Artist Two'], false, true, 2)],
                [],
                [new CsvExportProjectAssignmentLookupDto('project-1', [new ProjectTrackAssignmentDto('track-2', 1), new ProjectTrackAssignmentDto('track-1', 2)])]
            ),
            new CsvWriterService()
        );

        $download = $facade->exportProjects(new ProjectListFilterInputDto('spring', 'EP', 'active', 'title', 'ASC'));
        $rows     = readCsvRows($download->filePath);

        expect($download->downloadFilename)->toBe('projects-export.csv');
        expect($rows[0])->toBe(['ID', 'Titel', 'Kategorie', 'Interpreten', 'Published', 'Archiviert', 'Anzahl Tracks', 'Tracks']);
        expect($rows[1])->toBe(['project-1', 'Spring Tape', 'EP', 'Artist One, Artist Two', 'ja', 'nein', '2', 'Release Beta, Alpha Title']);

        unlink($download->filePath);
    });
});

/**
 * @return list<list<string>>
 */
function readCsvRows(string $filePath): array
{
    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        throw new RuntimeException('CSV file could not be opened.');
    }

    $rows  = [];
    $index = 0;
    while (($row = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
        if ($index === 0) {
            $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]) ?? (string) $row[0];
        }

        $rows[] = array_map(static fn (?string $value): string => (string) $value, $row);
        ++$index;
    }

    fclose($handle);

    return $rows;
}

final readonly class CsvExportTrackManagementFacadeStub implements TrackManagementFacadeInterface
{
    /**
     * @param list<TrackListExportItemDto> $tracks
     * @param list<TrackDto>               $tracksByUuid
     */
    public function __construct(
        private array $tracks,
        private array $tracksByUuid
    ) {
    }

    public function getTrackByUuid(string $trackUuid): TrackDto
    {
        foreach ($this->tracksByUuid as $track) {
            if ($track->uuid === $trackUuid) {
                return $track;
            }
        }

        throw new BadMethodCallException();
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
        return $this->tracks;
    }

    public function getAllTracksForSelection(): array
    {
        throw new BadMethodCallException();
    }
}

final readonly class CsvExportProjectManagementFacadeStub implements ProjectManagementFacadeInterface
{
    /**
     * @param list<ProjectListExportItemDto>            $projects
     * @param list<CsvExportTrackMembershipLookupDto>   $membershipsByTrackUuid
     * @param list<CsvExportProjectAssignmentLookupDto> $assignmentsByProjectUuid
     */
    public function __construct(
        private array $projects,
        private array $membershipsByTrackUuid,
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
        return [new ProjectCategoryDto('category-1', 'EP')];
    }

    public function getProjectsByFilter(ProjectListFilterInputDto $filter): array
    {
        return $this->projects;
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
        $memberships = [];
        foreach ($this->membershipsByTrackUuid as $lookup) {
            if ($lookup->trackUuid !== $trackUuid) {
                continue;
            }

            foreach ($lookup->memberships as $membership) {
                if ($membership->cancelled) {
                    continue;
                }

                $memberships[] = new TrackProjectMembershipDto(
                    $membership->projectUuid,
                    $membership->projectTitle,
                    $membership->categoryName,
                    $membership->position,
                    $membership->published
                );
            }
        }

        return $memberships;
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

final readonly class CsvExportTrackMembershipLookupDto
{
    /**
     * @param list<CsvExportTrackMembershipLookupItemDto> $memberships
     */
    public function __construct(
        public string $trackUuid,
        public array  $memberships
    ) {
    }
}

final readonly class CsvExportTrackMembershipLookupItemDto
{
    public function __construct(
        public string $projectUuid,
        public string $projectTitle,
        public string $categoryName,
        public int    $position,
        public bool   $published,
        public bool   $cancelled
    ) {
    }
}

final readonly class CsvExportProjectAssignmentLookupDto
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
