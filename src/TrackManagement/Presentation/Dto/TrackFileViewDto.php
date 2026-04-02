<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Dto;

readonly class TrackFileViewDto
{
    public function __construct(
        public string $originalFilename,
        public string $mimeType,
        public string $uploadedAt,
        public string $playbackUrl,
        public string $uploadUrl,
        public string $replaceUrl,
        public string $exportMp3Url,
        public string $exportWavUrl
    ) {
    }
}
