<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Facade;

use App\MediaAssetManagement\Domain\Service\ProjectMediaAssetDomainServiceInterface;
use App\MediaAssetManagement\Domain\Service\ProjectMediaAssetExportDomainServiceInterface;
use App\MediaAssetManagement\Facade\Dto\ExportedProjectMediaAssetDto;
use App\MediaAssetManagement\Facade\Dto\ProjectMediaAssetDto;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class MediaAssetManagementFacade implements MediaAssetManagementFacadeInterface
{
    public function __construct(
        private ProjectMediaAssetDomainServiceInterface       $projectMediaAssetDomainService,
        private ProjectMediaAssetExportDomainServiceInterface $projectMediaAssetExportDomainService
    ) {
    }

    public function uploadProjectMediaAsset(string $projectUuid, UploadedFile $uploadedFile): ProjectMediaAssetDto
    {
        return $this->projectMediaAssetDomainService->uploadProjectMediaAsset($projectUuid, $uploadedFile);
    }

    public function replaceProjectMediaAsset(string $projectUuid, UploadedFile $uploadedFile): ProjectMediaAssetDto
    {
        return $this->projectMediaAssetDomainService->replaceProjectMediaAsset($projectUuid, $uploadedFile);
    }

    public function getCurrentProjectMediaAssetByProjectUuid(string $projectUuid): ?ProjectMediaAssetDto
    {
        return $this->projectMediaAssetDomainService->getCurrentProjectMediaAsset($projectUuid);
    }

    public function deleteCurrentProjectMediaAssetByProjectUuid(string $projectUuid): void
    {
        $this->projectMediaAssetDomainService->deleteCurrentProjectMediaAsset($projectUuid);
    }

    public function exportProjectMediaAsset(string $projectUuid, string $targetFormat): ExportedProjectMediaAssetDto
    {
        return $this->projectMediaAssetExportDomainService->exportProjectMediaAsset($projectUuid, $targetFormat);
    }
}
