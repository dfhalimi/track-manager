<?php

declare(strict_types=1);

namespace App\FileImport\Infrastructure\Storage;

use App\FileImport\Infrastructure\Dto\StoredFileDto;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class TrackFileStorage implements TrackFileStorageInterface
{
    public function __construct(
        private string $projectDir
    ) {
    }

    public function storeUploadedFile(UploadedFile $file, string $storageFilename): StoredFileDto
    {
        $storageDirectory = $this->getStorageDirectory();
        if (!is_dir($storageDirectory)) {
            mkdir($storageDirectory, 0777, true);
        }

        $resolvedPath = $this->resolveStoragePath($storageFilename);
        $mimeType     = (string) ($file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream');
        $sizeBytes    = (int) $file->getSize();
        $extension    = mb_strtolower((string) pathinfo($storageFilename, PATHINFO_EXTENSION));

        $file->move($storageDirectory, $storageFilename);

        return new StoredFileDto(
            $storageFilename,
            $resolvedPath,
            $extension,
            $mimeType,
            $sizeBytes > 0 ? $sizeBytes : (int) filesize($resolvedPath)
        );
    }

    public function replaceStoredFile(string $oldFilename, UploadedFile $newFile, string $newStorageFilename): StoredFileDto
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

    public function buildStorageFilename(string $trackUuid, string $extension): string
    {
        return sprintf('%s.%s', $trackUuid, $extension);
    }

    public function resolveStoragePath(string $storedFilename): string
    {
        return $this->getStorageDirectory() . '/' . $storedFilename;
    }

    private function getStorageDirectory(): string
    {
        return $this->projectDir . '/var/storage/track-files';
    }
}
