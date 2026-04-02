<?php

declare(strict_types=1);

namespace App\FileImport\Facade;

use App\FileImport\Facade\Dto\TrackFileDto;

interface FileImportFacadeInterface
{
    public function getCurrentTrackFileByTrackUuid(string $trackUuid): ?TrackFileDto;

    public function deleteCurrentTrackFileByTrackUuid(string $trackUuid): void;
}
