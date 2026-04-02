<?php

declare(strict_types=1);

namespace App\FileImport\Infrastructure\Storage;

use App\FileImport\Infrastructure\Dto\StoredFileDto;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface TrackFileStorageInterface
{
    public function storeUploadedFile(UploadedFile $file, string $storageFilename): StoredFileDto;

    public function replaceStoredFile(string $oldFilename, UploadedFile $newFile, string $newStorageFilename): StoredFileDto;

    public function deleteStoredFile(string $storedFilename): void;

    public function buildStorageFilename(string $trackUuid, string $extension): string;

    public function resolveStoragePath(string $storedFilename): string;
}
