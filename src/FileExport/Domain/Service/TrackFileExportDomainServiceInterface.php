<?php

declare(strict_types=1);

namespace App\FileExport\Domain\Service;

use App\FileExport\Domain\Dto\ExportTrackFileInputDto;
use App\FileExport\Facade\Dto\ExportedTrackFileDto;
use App\TrackManagement\Facade\Dto\TrackExportDataDto;

interface TrackFileExportDomainServiceInterface
{
    public function exportTrackFile(ExportTrackFileInputDto $input): ExportedTrackFileDto;

    public function buildExportFilename(TrackExportDataDto $trackExportData, string $targetFormat): string;
}
