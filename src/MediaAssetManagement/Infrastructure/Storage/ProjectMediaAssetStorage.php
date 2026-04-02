<?php

declare(strict_types=1);

namespace App\MediaAssetManagement\Infrastructure\Storage;

use App\MediaAssetManagement\Infrastructure\Dto\StoredProjectMediaAssetDto;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ValueError;

readonly class ProjectMediaAssetStorage implements ProjectMediaAssetStorageInterface
{
    public function __construct(
        private string $projectDir
    ) {
    }

    public function storeUploadedFile(UploadedFile $file, string $storageFilename): StoredProjectMediaAssetDto
    {
        $storageDirectory = $this->getStorageDirectory();
        if (!is_dir($storageDirectory)) {
            mkdir($storageDirectory, 0777, true);
        }

        $resolvedPath = $this->resolveStoragePath($storageFilename);
        $mimeType = (string) ($file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream');
        $sizeBytes = (int) $file->getSize();
        $extension = mb_strtolower((string) pathinfo($storageFilename, PATHINFO_EXTENSION));

        $file->move($storageDirectory, $storageFilename);

        $imageSize = getimagesize($resolvedPath);
        if (!is_array($imageSize)) {
            throw new ValueError('Uploaded image dimensions could not be detected.');
        }

        return new StoredProjectMediaAssetDto(
            $storageFilename,
            $resolvedPath,
            $extension,
            $mimeType,
            $sizeBytes > 0 ? $sizeBytes : (int) filesize($resolvedPath),
            (int) $imageSize[0],
            (int) $imageSize[1]
        );
    }

    public function replaceStoredFile(string $oldFilename, UploadedFile $newFile, string $newStorageFilename): StoredProjectMediaAssetDto
    {
        $this->deleteStoredFile($oldFilename);

        return $this->storeUploadedFile($newFile, $newStorageFilename);
    }

    public function deleteStoredFile(string $storedFilename): void
    {
        $path = $this->resolveStoragePath($storedFilename);
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function buildStorageFilename(string $projectUuid, string $extension): string
    {
        return sprintf('%s.%s', $projectUuid, $extension);
    }

    public function resolveStoragePath(string $storedFilename): string
    {
        return $this->getStorageDirectory() . '/' . $storedFilename;
    }

    private function getStorageDirectory(): string
    {
        return $this->projectDir . '/var/storage/project-media-assets';
    }
}
