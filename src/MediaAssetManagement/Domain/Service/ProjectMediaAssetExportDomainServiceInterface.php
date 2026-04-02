<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Domain\Service;

use App\MediaAssetManagement\Facade\Dto\ExportedProjectMediaAssetDto;

interface ProjectMediaAssetExportDomainServiceInterface
{
    public function exportProjectMediaAsset(string $projectUuid, string $targetFormat): ExportedProjectMediaAssetDto;
}
