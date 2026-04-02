<?php

declare(strict_types=1);

use App\FileExport\Domain\Dto\ExportProjectFilesInputDto;
use App\FileExport\Domain\Dto\ExportTrackFileInputDto;
use App\FileExport\Domain\Service\ProjectFileExportDomainService;
use App\FileExport\Domain\Service\TrackFileExportDomainServiceInterface;
use App\FileExport\Facade\Dto\ExportedTrackFileDto;
use App\ProjectManagement\Facade\Dto\ProjectCategoryDto;
use App\ProjectManagement\Facade\Dto\ProjectDto;
use App\ProjectManagement\Facade\Dto\ProjectTrackAssignmentDto;
use App\ProjectManagement\Facade\Dto\TrackProjectMembershipDto;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;

describe('ProjectFileExportDomainService', function (): void {
    it('exports all available project tracks into one zip archive', function (): void {
        $trackExportService = new TrackFileExportDomainServiceStub([
            new TrackExportStubEntry('track-1', createExportedTrackFile('spring_tape.mp3', 'audio/mpeg')),
            new TrackExportStubEntry('track-3', createExportedTrackFile('night_drive.mp3', 'audio/mpeg')),
        ]);

        $service = new ProjectFileExportDomainService(
            new ProjectManagementFacadeStub(
                new ProjectDto('project-1', 'Spring Tape', 'category-1', 'EP', DateAndTimeService::getDateTimeImmutable(), DateAndTimeService::getDateTimeImmutable()),
                [
                    new ProjectTrackAssignmentDto('track-1', 1),
                    new ProjectTrackAssignmentDto('track-2', 2),
                    new ProjectTrackAssignmentDto('track-3', 3),
                ]
            ),
            $trackExportService
        );

        $archive = $service->exportProjectFiles(new ExportProjectFilesInputDto('project-1', 'mp3'));

        expect($archive->downloadFilename)->toBe('Spring_Tape_all_mp3.zip');
        expect($archive->mimeType)->toBe('application/zip');
        expect(is_file($archive->filePath))->toBeTrue();

        $zipArchive = new ZipArchive();
        expect($zipArchive->open($archive->filePath))->toBeTrue();
        expect($zipArchive->numFiles)->toBe(2);
        expect($zipArchive->getNameIndex(0))->toBe('01_spring_tape.mp3');
        expect($zipArchive->getNameIndex(1))->toBe('03_night_drive.mp3');
        $zipArchive->close();

        unlink($archive->filePath);
        $trackExportService->cleanup();
    });

    it('rejects bulk export when the project has no exportable audio files', function (): void {
        $trackExportService = new TrackFileExportDomainServiceStub([]);

        $service = new ProjectFileExportDomainService(
            new ProjectManagementFacadeStub(
                new ProjectDto('project-1', 'Spring Tape', 'category-1', 'EP', DateAndTimeService::getDateTimeImmutable(), DateAndTimeService::getDateTimeImmutable()),
                [
                    new ProjectTrackAssignmentDto('track-1', 1),
                ]
            ),
            $trackExportService
        );

        $action = static fn () => $service->exportProjectFiles(new ExportProjectFilesInputDto('project-1', 'mp3'));

        expect($action)->toThrow(ValueError::class, 'Dieses Projekt enthält noch keine Audio-Dateien zum Exportieren.');
    });
});

function createExportedTrackFile(string $downloadFilename, string $mimeType): ExportedTrackFileDto
{
    $filePath = tempnam(sys_get_temp_dir(), 'project-bulk-export-');
    if ($filePath === false) {
        throw new RuntimeException('Temporary file could not be created.');
    }

    file_put_contents($filePath, 'audio');

    return new ExportedTrackFileDto($filePath, $downloadFilename, $mimeType);
}

final class ProjectManagementFacadeStub implements ProjectManagementFacadeInterface
{
    /**
     * @param list<ProjectTrackAssignmentDto> $assignments
     */
    public function __construct(
        private readonly ProjectDto $project,
        private readonly array      $assignments
    ) {
    }

    public function getProjectByUuid(string $projectUuid): ProjectDto
    {
        return $this->project;
    }

    public function projectExists(string $projectUuid): bool
    {
        return $projectUuid === $this->project->uuid;
    }

    public function getAllProjectCategories(): array
    {
        return [new ProjectCategoryDto('category-1', 'EP')];
    }

    public function getTrackAssignmentsByProjectUuid(string $projectUuid): array
    {
        return $this->assignments;
    }

    public function getProjectsByTrackUuid(string $trackUuid): array
    {
        return [new TrackProjectMembershipDto($this->project->uuid, $this->project->title, $this->project->categoryName, 1)];
    }

    public function removeTrackFromAllProjects(string $trackUuid): void
    {
    }
}

final class TrackFileExportDomainServiceStub implements TrackFileExportDomainServiceInterface
{
    /**
     * @param list<TrackExportStubEntry> $exports
     */
    public function __construct(
        private array $exports
    ) {
    }

    public function exportTrackFile(ExportTrackFileInputDto $input): ExportedTrackFileDto
    {
        foreach ($this->exports as $export) {
            if ($export->trackUuid !== $input->trackUuid) {
                continue;
            }

            return $export->exportedTrackFile;
        }

        throw new ValueError('Track has no current audio file to export.');
    }

    public function buildExportFilename(App\TrackManagement\Facade\Dto\TrackExportDataDto $trackExportData, string $targetFormat): string
    {
        return sprintf('%s.%s', $trackExportData->title, $targetFormat);
    }

    public function cleanup(): void
    {
        foreach ($this->exports as $export) {
            if (is_file($export->exportedTrackFile->filePath)) {
                unlink($export->exportedTrackFile->filePath);
            }
        }
    }
}

final readonly class TrackExportStubEntry
{
    public function __construct(
        public string               $trackUuid,
        public ExportedTrackFileDto $exportedTrackFile
    ) {
    }
}
