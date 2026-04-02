<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Dto;

readonly class ProjectMediaAssetViewDto
{
    public function __construct(
        public string $originalFilename,
        public string $mimeType,
        public string $uploadedAt,
        public string $dimensionsLabel,
        public string $previewUrl,
        public string $replaceUrl,
        public string $exportJpgUrl,
        public string $exportPngUrl
    ) {
    }
}
