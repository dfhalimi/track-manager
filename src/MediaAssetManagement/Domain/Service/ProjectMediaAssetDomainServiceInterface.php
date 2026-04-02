<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Domain\Service;

use App\MediaAssetManagement\Facade\Dto\ProjectMediaAssetDto;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ProjectMediaAssetDomainServiceInterface
{
    public function uploadProjectMediaAsset(string $projectUuid, UploadedFile $uploadedFile): ProjectMediaAssetDto;

    public function replaceProjectMediaAsset(string $projectUuid, UploadedFile $uploadedFile): ProjectMediaAssetDto;

    public function getCurrentProjectMediaAsset(string $projectUuid): ?ProjectMediaAssetDto;

    public function deleteCurrentProjectMediaAsset(string $projectUuid): void;
}
