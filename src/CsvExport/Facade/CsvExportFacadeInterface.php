<?php

declare(strict_types=1);

namespace App\CsvExport\Facade;

use App\CsvExport\Facade\Dto\CsvDownloadDto;
use App\ProjectManagement\Facade\Dto\ProjectListFilterInputDto;
use App\TrackManagement\Facade\Dto\TrackListFilterInputDto;

interface CsvExportFacadeInterface
{
    public function exportTracks(TrackListFilterInputDto $filter): CsvDownloadDto;

    public function exportProjects(ProjectListFilterInputDto $filter): CsvDownloadDto;
}
