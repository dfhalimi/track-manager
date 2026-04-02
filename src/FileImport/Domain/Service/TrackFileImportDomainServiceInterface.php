<?php

declare(strict_types=1);

namespace App\FileImport\Domain\Service;

use App\FileImport\Domain\Dto\ReplaceTrackFileInputDto;
use App\FileImport\Domain\Dto\UploadTrackFileInputDto;
use App\FileImport\Facade\Dto\TrackFileDto;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface TrackFileImportDomainServiceInterface
{
    public function uploadTrackFile(UploadTrackFileInputDto $input): TrackFileDto;

    public function replaceTrackFile(ReplaceTrackFileInputDto $input): TrackFileDto;

    public function getCurrentTrackFile(string $trackUuid): ?TrackFileDto;

    public function assertSupportedAudioFile(UploadedFile $file): void;

    public function deleteCurrentTrackFile(string $trackUuid): void;
}
