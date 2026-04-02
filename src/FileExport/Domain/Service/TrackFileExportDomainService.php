<?php

declare(strict_types=1);

namespace App\FileExport\Domain\Service;

use App\FileExport\Domain\Dto\ExportTrackFileInputDto;
use App\FileExport\Facade\Dto\ExportedTrackFileDto;
use App\FileExport\Infrastructure\Audio\AudioConversionServiceInterface;
use App\FileImport\Facade\FileImportFacadeInterface;
use App\TrackManagement\Facade\Dto\TrackExportDataDto;
use App\TrackManagement\Facade\TrackManagementFacadeInterface;
use ValueError;

readonly class TrackFileExportDomainService implements TrackFileExportDomainServiceInterface
{
    public function __construct(
        private TrackManagementFacadeInterface  $trackManagementFacade,
        private FileImportFacadeInterface       $fileImportFacade,
        private AudioConversionServiceInterface $audioConversionService
    ) {
    }

    public function exportTrackFile(ExportTrackFileInputDto $input): ExportedTrackFileDto
    {
        $targetFormat = mb_strtolower(trim($input->targetFormat));
        if (!in_array($targetFormat, ['mp3', 'wav'], true)) {
            throw new ValueError('Only MP3 and WAV exports are supported.');
        }

        $trackExportData = $this->trackManagementFacade->getTrackExportData($input->trackUuid);
        $trackFile       = $this->fileImportFacade->getCurrentTrackFileByTrackUuid($input->trackUuid);

        if ($trackFile === null) {
            throw new ValueError('Track has no current audio file to export.');
        }

        $downloadFilename  = $this->buildExportFilename($trackExportData, $targetFormat);
        $temporaryFilePath = sys_get_temp_dir() . '/' . uniqid('track-export-', true) . '.' . $targetFormat;

        if ($trackFile->extension === $targetFormat) {
            copy($trackFile->storagePath, $temporaryFilePath);
        } else {
            $this->audioConversionService->convert($trackFile->storagePath, $temporaryFilePath, $targetFormat);
        }

        return new ExportedTrackFileDto(
            $temporaryFilePath,
            $downloadFilename,
            $targetFormat === 'mp3' ? 'audio/mpeg' : 'audio/wav'
        );
    }

    public function buildExportFilename(TrackExportDataDto $trackExportData, string $targetFormat): string
    {
        $baseName = trim($trackExportData->title);
        $baseName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $baseName) ?? $baseName;
        $baseName = trim($baseName, '_');

        if ($baseName === '') {
            $bpms = implode(
                '_',
                array_map(
                    static fn (int $bpm): string => sprintf('%dBPM', $bpm),
                    $trackExportData->bpms
                )
            );

            $baseName = sprintf(
                '%d_%s_%s_%s',
                $trackExportData->trackNumber,
                $trackExportData->beatName,
                $bpms,
                implode('_', $trackExportData->musicalKeys)
            );
        }

        return sprintf('%s.%s', $baseName, $targetFormat);
    }
}
