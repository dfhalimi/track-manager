<?php

declare(strict_types=1);

namespace App\FileExport\Facade;

use App\FileExport\Domain\Dto\ExportTrackFileInputDto;
use App\FileExport\Facade\Dto\ExportedTrackFileDto;

interface FileExportFacadeInterface
{
    public function exportTrackFile(ExportTrackFileInputDto $input): ExportedTrackFileDto;
}
