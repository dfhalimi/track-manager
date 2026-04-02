<?php

declare(strict_types=1);

namespace App\FileImport\Domain\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class UploadTrackFileInputDto
{
    public function __construct(
        public string       $trackUuid,
        public UploadedFile $uploadedFile
    ) {
    }
}
