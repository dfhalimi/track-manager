<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Infrastructure\Storage;

use App\MediaAssetManagement\Infrastructure\Dto\StoredProjectMediaAssetDto;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ProjectMediaAssetStorageInterface
{
    public function storeUploadedFile(UploadedFile $file, string $storageFilename): StoredProjectMediaAssetDto;

    public function replaceStoredFile(string $oldFilename, UploadedFile $newFile, string $newStorageFilename): StoredProjectMediaAssetDto;

    public function deleteStoredFile(string $storedFilename): void;

    public function buildStorageFilename(string $projectUuid, string $extension): string;

    public function resolveStoragePath(string $storedFilename): string;
}
