<?php

declare(strict_types=1);

namespace App\FileImport\Domain\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class ReplaceTrackFileInputDto
{
    public function __construct(
        public string       $trackUuid,
        public UploadedFile $uploadedFile
    ) {
    }
}
