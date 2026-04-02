<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Facade;

use App\MediaAssetManagement\Facade\Dto\ExportedProjectMediaAssetDto;
use App\MediaAssetManagement\Facade\Dto\ProjectMediaAssetDto;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface MediaAssetManagementFacadeInterface
{
    public function uploadProjectMediaAsset(string $projectUuid, UploadedFile $uploadedFile): ProjectMediaAssetDto;

    public function replaceProjectMediaAsset(string $projectUuid, UploadedFile $uploadedFile): ProjectMediaAssetDto;

    public function getCurrentProjectMediaAssetByProjectUuid(string $projectUuid): ?ProjectMediaAssetDto;

    public function deleteCurrentProjectMediaAssetByProjectUuid(string $projectUuid): void;

    public function exportProjectMediaAsset(string $projectUuid, string $targetFormat): ExportedProjectMediaAssetDto;
}
