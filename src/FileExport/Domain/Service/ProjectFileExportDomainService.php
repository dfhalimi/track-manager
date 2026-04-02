<?php

declare(strict_types=1);

namespace App\FileExport\Domain\Service;

use App\FileExport\Domain\Dto\ExportProjectFilesInputDto;
use App\FileExport\Domain\Dto\ExportTrackFileInputDto;
use App\FileExport\Facade\Dto\ExportedProjectArchiveDto;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use RuntimeException;
use ValueError;
use ZipArchive;

readonly class ProjectFileExportDomainService implements ProjectFileExportDomainServiceInterface
{
    public function __construct(
        private ProjectManagementFacadeInterface      $projectManagementFacade,
        private TrackFileExportDomainServiceInterface $trackFileExportDomainService,
        private TrackManagementFacadeInterface        $trackManagementFacade
    ) {
    }

    public function exportProjectFiles(ExportProjectFilesInputDto $input): ExportedProjectArchiveDto
    {
        $targetFormat = mb_strtolower(trim($input->targetFormat));
        if (!in_array($targetFormat, ['mp3', 'wav'], true)) {
            throw new ValueError('Only MP3 and WAV exports are supported.');
        }

        $project = $this->projectManagementFacade->getProjectByUuid($input->projectUuid);

        $temporaryArchivePath = sys_get_temp_dir() . '/' . uniqid('project-export-', true) . '.zip';
        $zipArchive           = new ZipArchive();
        $openedArchive        = $zipArchive->open($temporaryArchivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($openedArchive !== true) {
            throw new RuntimeException('Projekt-Export konnte nicht erstellt werden.');
        }

        $temporaryTrackFiles = [];
        $exportedTrackCount  = 0;

        try {
            foreach ($this->projectManagementFacade->getTrackAssignmentsByProjectUuid($input->projectUuid) as $assignment) {
                if ($this->trackManagementFacade->getTrackByUuid($assignment->trackUuid)->cancelled) {
                    continue;
                }

                try {
                    $exportedTrackFile = $this->trackFileExportDomainService->exportTrackFile(
                        new ExportTrackFileInputDto($assignment->trackUuid, $targetFormat)
                    );
                } catch (ValueError) {
                    continue;
                }

                $archiveEntryName = sprintf('%02d_%s', $assignment->position, $exportedTrackFile->downloadFilename);
                $zipArchive->addFile($exportedTrackFile->filePath, $archiveEntryName);

                $temporaryTrackFiles[] = $exportedTrackFile->filePath;
                ++$exportedTrackCount;
            }

            $zipArchive->close();

            if ($exportedTrackCount === 0) {
                $this->deleteFileIfExists($temporaryArchivePath);

                throw new ValueError('Dieses Projekt enthält noch keine Audio-Dateien zum Exportieren.');
            }

            return new ExportedProjectArchiveDto(
                $temporaryArchivePath,
                $this->buildArchiveFilename($project->title, $targetFormat),
                'application/zip'
            );
        } finally {
            foreach ($temporaryTrackFiles as $temporaryTrackFile) {
                $this->deleteFileIfExists($temporaryTrackFile);
            }
        }
    }

    private function buildArchiveFilename(string $projectTitle, string $targetFormat): string
    {
        $baseName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', trim($projectTitle)) ?? trim($projectTitle);
        $baseName = trim($baseName, '_');
        if ($baseName === '') {
            $baseName = 'project';
        }

        return sprintf('%s_all_%s.zip', $baseName, $targetFormat);
    }

    private function deleteFileIfExists(string $filePath): void
    {
        if (!is_file($filePath)) {
            return;
        }

        unlink($filePath);
    }
}
