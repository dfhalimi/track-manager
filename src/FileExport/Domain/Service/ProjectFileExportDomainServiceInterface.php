<?php

declare(strict_types=1);

namespace App\FileExport\Domain\Service;

use App\FileExport\Domain\Dto\ExportProjectFilesInputDto;
use App\FileExport\Facade\Dto\ExportedProjectArchiveDto;

interface ProjectFileExportDomainServiceInterface
{
    public function exportProjectFiles(ExportProjectFilesInputDto $input): ExportedProjectArchiveDto;
}
