<?php

declare(strict_types=1);

namespace App\FileExport\Facade;

use App\FileExport\Domain\Dto\ExportTrackFileInputDto;
use App\FileExport\Domain\Service\TrackFileExportDomainServiceInterface;
use App\FileExport\Facade\Dto\ExportedTrackFileDto;

readonly class FileExportFacade implements FileExportFacadeInterface
{
    public function __construct(
        private TrackFileExportDomainServiceInterface $trackFileExportDomainService
    ) {
    }

    public function exportTrackFile(ExportTrackFileInputDto $input): ExportedTrackFileDto
    {
        return $this->trackFileExportDomainService->exportTrackFile($input);
    }
}
